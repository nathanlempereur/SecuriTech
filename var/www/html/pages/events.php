<?php
require_once __DIR__ . '/updateZone.php';
require_once __DIR__ . '/../config/datetime.php';

updateZonesFromCsv();

$search_reader = trim((string) ($_GET['badgeuse'] ?? ''));
$search_badge = trim((string) ($_GET['uid'] ?? ''));
$date_from = trim((string) ($_GET['de'] ?? ''));
$date_to = trim((string) ($_GET['a'] ?? ''));
$date_from_sql = normalizeLocalDateTimeToUtc($date_from);
$date_to_sql = normalizeLocalDateTimeToUtc($date_to);
$sort = (string) ($_GET['tri'] ?? 'date_heure');
$direction = strtolower((string) ($_GET['ordre'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$page_number = max(1, (int) ($_GET['page_num'] ?? 1));
$items_per_page = 20;

$sort_columns = [
    'date_heure' => 'log_acces.date_heure',
    'badge' => 'log_acces.numero_serie_lu',
    'zone' => 'zone.nom',
    'reader' => 'badgeuse.code',
    'result' => 'log_acces.resultat',
];
$sort = array_key_exists($sort, $sort_columns) ? $sort : 'date_heure';

$where = [];
$parameters = [];

if ($search_reader !== '') {
    $where[] = 'badgeuse.code ILIKE :badgeuse';
    $parameters['badgeuse'] = '%' . $search_reader . '%';
}
if ($search_badge !== '') {
    $where[] = 'log_acces.numero_serie_lu ILIKE :uid';
    $parameters['uid'] = '%' . $search_badge . '%';
}
if ($date_from_sql !== '') {
    $where[] = 'log_acces.date_heure >= CAST(:date_from AS timestamp)';
    $parameters['date_from'] = $date_from_sql;
}
if ($date_to_sql !== '') {
    $where[] = "log_acces.date_heure < CAST(:date_to AS timestamp) + INTERVAL '1 minute'";
    $parameters['date_to'] = $date_to_sql;
}

$where_sql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
$joins_sql = <<<'SQL'
    FROM log_acces
    INNER JOIN badgeuse ON badgeuse.id = log_acces.badgeuse_id
    INNER JOIN zone ON zone.id = log_acces.zone_id
SQL;

$count_statement = $pdo->prepare("SELECT COUNT(*) {$joins_sql} {$where_sql}");
$count_statement->execute($parameters);
$total_events = (int) $count_statement->fetchColumn();
$total_pages = max(1, (int) ceil($total_events / $items_per_page));
$page_number = min($page_number, $total_pages);
$offset = ($page_number - 1) * $items_per_page;

$events_statement = $pdo->prepare(<<<SQL
    SELECT
        log_acces.date_heure,
        log_acces.numero_serie_lu,
        log_acces.resultat,
        badgeuse.code AS badgeuse_code,
        zone.nom AS zone_nom
    {$joins_sql}
    {$where_sql}
    ORDER BY {$sort_columns[$sort]} {$direction}
    LIMIT :limit OFFSET :offset
SQL);

foreach ($parameters as $name => $value) {
    $events_statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$events_statement->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$events_statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$events_statement->execute();
$visible_events = $events_statement->fetchAll();

$query_parameters = [
    'page' => 'events',
    'badgeuse' => $search_reader,
    'uid' => $search_badge,
    'de' => $date_from,
    'a' => $date_to,
    'tri' => $sort,
    'ordre' => strtolower($direction),
];

$build_url = static function (array $parameters): string {
    return 'index.php?' . http_build_query(array_filter($parameters, static fn ($value): bool => $value !== '' && $value !== null));
};

$sort_url = static function (string $column) use ($query_parameters, $sort, $direction, $build_url): string {
    $next_direction = $sort === $column && $direction === 'ASC' ? 'desc' : 'asc';

    return $build_url(array_merge($query_parameters, ['tri' => $column, 'ordre' => $next_direction, 'page_num' => 1]));
};
?>

<main class="page-content events-page">
    <section class="access-heading events-heading" aria-labelledby="events-page-title">
        <div>
            <p class="eyebrow">EVENEMENT_ACCES / ARCHIVES</p>
            <h1 id="events-page-title">Tous les <span>accès</span></h1>
            <p class="intro">Recherchez, filtrez et triez les événements enregistrés par les badgeuses.</p>
        </div>
        <div class="demo-status"><span></span> <?= $total_events ?> événement<?= $total_events > 1 ? 's' : '' ?> Supabase</div>
    </section>

    <section class="dashboard-section event-filters" aria-labelledby="filters-title">
        <div class="section-heading">
            <div><p class="eyebrow">FILTRES DE JOURNAL</p><h2 id="filters-title">Affiner les accès</h2></div>
            <a class="clear-filters" href="index.php?page=events">Réinitialiser</a>
        </div>
        <form class="event-filter-form" method="get" action="index.php">
            <input type="hidden" name="page" value="events">
            <label><span>Badgeuse</span><input type="search" name="badgeuse" value="<?= htmlspecialchars($search_reader, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. BAD-003"></label>
            <label><span>UID du badge</span><input type="search" name="uid" value="<?= htmlspecialchars($search_badge, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. 04:A8:91:2F"></label>
            <label><span>Du</span><input type="datetime-local" name="de" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label><span>Au</span><input type="datetime-local" name="a" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>"></label>
            <button class="button filter-button" type="submit">Rechercher</button>
        </form>
    </section>

    <section class="dashboard-section event-table-section" aria-labelledby="event-table-title">
        <div class="section-heading">
            <div><p class="eyebrow"><?= $total_events ?> RÉSULTAT<?= $total_events > 1 ? 'S' : '' ?> / PAGE <?= $page_number ?> SUR <?= $total_pages ?></p><h2 id="event-table-title">Journal des événements</h2></div>
            <span class="section-count">20 lignes maximum</span>
        </div>
        <div class="table-wrapper">
            <table class="data-table event-data-table">
                <thead><tr>
                    <?php
                    $columns = ['date_heure' => 'Date et heure', 'badge' => 'Badge UID', 'zone' => 'Zone', 'reader' => 'Badgeuse', 'result' => 'Résultat'];
                    foreach ($columns as $column => $label):
                        $is_active_sort = $sort === $column;
                        $arrow = $is_active_sort ? ($direction === 'ASC' ? '↑' : '↓') : '↕';
                    ?>
                        <th class="sortable-header <?= $is_active_sort ? 'is-sorted' : '' ?>"><a href="<?= htmlspecialchars($sort_url($column), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> <span><?= $arrow ?></span></a></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                    <?php if ($visible_events === []): ?>
                        <tr><td class="empty-table" colspan="5">Aucun événement ne correspond à ces filtres.</td></tr>
                    <?php else: ?>
                        <?php foreach ($visible_events as $event): ?>
                            <?php $is_allowed = strtoupper((string) $event['resultat']) === 'AUTORISE'; ?>
                            <tr>
                                <td class="table-time"><?= htmlspecialchars(formatFrenchDateTime($event['date_heure']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($event['numero_serie_lu'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($event['zone_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($event['badgeuse_code'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="result <?= $is_allowed ? 'is-success' : 'is-danger' ?>"><?= htmlspecialchars($event['resultat'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Pagination des événements">
                <?php for ($page_index = 1; $page_index <= $total_pages; $page_index++): ?><a class="pagination-link <?= $page_index === $page_number ? 'is-current' : '' ?>" href="<?= htmlspecialchars($build_url(array_merge($query_parameters, ['page_num' => $page_index])), ENT_QUOTES, 'UTF-8') ?>"><?= $page_index ?></a><?php endfor; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>
