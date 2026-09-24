<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Synchronise les zones du CSV vers Supabase.
 *
 * Le chemin peut être défini avec ZONES_CSV_PATH dans .env. La fonction
 * retourne false sans modifier la base si le fichier n'est pas accessible.
 */
function updateZonesFromCsv(?string $csvPath = null): bool
{
    global $pdo;

    $csvPath = $csvPath ?? zonesCsvPath();
    $handle = @fopen($csvPath, 'rb');

    if ($handle === false) {
        return false;
    }

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) < 3) {
            continue;
        }

        $name = trim((string) $row[0], " \t\r\n\xEF\xBB\xBF");
        $address = trim((string) $row[1]);
        $status = strtolower(trim((string) $row[2]));

        if ($name === '' || $address === '' || ($status !== '' && !array_key_exists($status, [
            '1' => true,
            '0' => true,
            'true' => true,
            'false' => true,
            'oui' => true,
            'non' => true,
            'on' => true,
            'off' => true,
            'up' => true,
            'down' => true,
            'actif' => true,
            'inactif' => true,
            'active' => true,
            'inactive' => true,
            'online' => true,
            'offline' => true,
        ]))) {
            continue;
        }

        if (strtolower($name) === 'nomzone' && strtolower($address) === 'adresseip') {
            continue;
        }

        $rows[] = [
            'nom' => $name,
            'adresse_ip' => $address,
            'etat' => !in_array($status, ['0', 'false', 'non', 'off', 'down', 'inactif', 'inactive', 'offline'], true),
        ];
    }
    fclose($handle);

    if ($rows === []) {
        return true;
    }

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(<<<'SQL'
            INSERT INTO zone (nom, etat, "adresseIP")
            VALUES (:nom, :etat, :adresse_ip)
            ON CONFLICT (nom) DO UPDATE SET
                etat = EXCLUDED.etat,
                "adresseIP" = EXCLUDED."adresseIP"
        SQL);

        foreach ($rows as $zone) {
            $statement->execute([
                'nom' => $zone['nom'],
                'etat' => $zone['etat'],
                'adresse_ip' => $zone['adresse_ip'],
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function zonesCsvPath(): string
{
    $environment = parse_ini_file(__DIR__ . '/../.env');
    $configuredPath = $environment['ZONES_CSV_PATH'] ?? getenv('ZONES_CSV_PATH');

    return is_string($configuredPath) && trim($configuredPath) !== ''
        ? $configuredPath
        : __DIR__ . '/../database/zones.csv';
}
