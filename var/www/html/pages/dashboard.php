<?php
require_once __DIR__ . '/updateZone.php';
require_once __DIR__ . '/../config/datetime.php';

updateZonesFromCsv();

$nb_users = (int) $pdo->query(
    "SELECT COUNT(*) FROM utilisateur WHERE etat = TRUE"
)->fetchColumn();

$nb_badges = (int) $pdo->query(
    "SELECT COUNT(*) FROM badge WHERE etat = TRUE"
)->fetchColumn();

$recent_events = $pdo->query(<<<'SQL'
    SELECT
        log_acces.date_heure,
        log_acces.numero_serie_lu,
        log_acces.resultat,
        badgeuse.code AS badgeuse_code,
        zone.nom AS zone_nom
    FROM log_acces
    INNER JOIN badgeuse ON badgeuse.id = log_acces.badgeuse_id
    INNER JOIN zone ON zone.id = log_acces.zone_id
    ORDER BY log_acces.date_heure DESC
    LIMIT 4
SQL
)->fetchAll();

$zones = $pdo->query(<<<'SQL'
    SELECT nom, criticite, "adresseIP" AS adresse_ip
    FROM zone
    ORDER BY id
SQL
)->fetchAll();

$criticality_labels = [
    1 => 'Normale',
    2 => 'Faible',
    3 => 'Modérée',
    4 => 'Élevée',
    5 => 'Critique',
];
?>

<main class="page-content access-page">
    <section class="access-heading" aria-labelledby="access-title">
        <div>
            <p class="eyebrow">Centre de supervision</p>
            <h1 id="access-title">Dash<span>board</span></h1>
            <p class="intro">Une vue d'ensemble des utilisateurs, des badges, des zones et des derniers accès.</p>
        </div>
        <div class="demo-status"><span></span> Données Supabase</div>
    </section>

    <section class="stats-grid" aria-label="Indicateurs d'accès">
        <article class="stat-card">
            <p>Utilisateurs actifs</p>
            <strong><?= $nb_users ?></strong>
            <span class="stat-detail positive">État utilisateur actif</span>
        </article>
        <article class="stat-card">
            <p>Badges actifs</p>
            <strong><?= $nb_badges ?></strong>
            <span class="stat-detail positive">État badge actif</span>
        </article>
    </section>

    <section class="dashboard-section" aria-labelledby="events-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EVENEMENT_ACCES</p>
                <h2 id="events-title">Activité récente</h2>
            </div>
            <span class="live-indicator"><span></span> Données en direct</span>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Badge UID</th>
                        <th>Zone</th>
                        <th>Badgeuse</th>
                        <th>Résultat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_events === []): ?>
                        <tr>
                            <td class="empty-table" colspan="5">Aucun accès enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_events as $event): ?>
                            <?php $is_allowed = strtoupper((string) $event['resultat']) === 'AUTORISE'; ?>
                            <tr>
                                <td class="table-time"><?= htmlspecialchars(formatFrenchDateTime($event['date_heure']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($event['numero_serie_lu'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($event['zone_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($event['badgeuse_code'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="result <?= $is_allowed ? 'is-success' : 'is-danger' ?>">
                                        <?= htmlspecialchars($event['resultat'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a class="table-more-link" href="index.php?page=events">Voir tous les accès <span aria-hidden="true">→</span></a>
    </section>

    <section class="dashboard-section zones-section" aria-labelledby="zones-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ZONE</p>
                <h2 id="zones-title">Zones</h2>
            </div>
            <span class="section-count"><?= count($zones) ?> zone<?= count($zones) > 1 ? 's' : '' ?></span>
        </div>
        <div class="zone-list">
            <?php foreach ($zones as $zone): ?>
                <?php
                $criticality = (int) $zone['criticite'];
                $criticality_label = $criticality_labels[$criticality] ?? 'Inconnue';
                $address = trim((string) ($zone['adresse_ip'] ?? ''));
                $address_url = $address === ''
                    ? null
                    : (preg_match('/^https?:\/\//i', $address) === 1 ? $address : 'http://' . $address);
                $address_url = filter_var($address_url, FILTER_VALIDATE_URL) ? $address_url : null;
                ?>
                <article
                    class="zone-item <?= $address_url !== null ? 'has-zone-link' : '' ?>"
                    <?= $address_url !== null ? 'data-zone-url="' . htmlspecialchars($address_url, ENT_QUOTES, 'UTF-8') . '" role="link" tabindex="0" title="Ouvrir la zone"' : '' ?>
                >
                    <div class="zone-item-content">
                        <h3><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <span class="tag <?= $criticality >= 5 ? 'tag-critical' : ($criticality >= 4 ? 'tag-high' : 'tag-normal') ?>">
                        Criticité <?= $criticality ?> · <?= $criticality_label ?>
                    </span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
