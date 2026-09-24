<?php

$env = parse_ini_file(__DIR__ . '/../.env');

if ($env === false) {
    die('Configuration de la base de données introuvable.');
}

// Création de la chaîne de connexion à la bdd à partir des variables d'environnement
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
    $env['DB_HOST'],
    $env['DB_PORT'],
    $env['DB_NAME']
);

try {
    $pdo = new PDO(
        $dsn,
        $env['DB_USER'],
        $env['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
