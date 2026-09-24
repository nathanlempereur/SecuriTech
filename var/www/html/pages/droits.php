<?php
require_once __DIR__ . '/updateZone.php';

updateZonesFromCsv();

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            $date_start = trim((string) ($_POST['date_debut'] ?? ''));
            $date_end = trim((string) ($_POST['date_fin'] ?? ''));
            $date_start = $date_start === '' ? null : str_replace('T', ' ', $date_start);
            $date_end = $date_end === '' ? null : str_replace('T', ' ', $date_end);
            $values = [
                'utilisateur_id' => (int) ($_POST['utilisateur_id'] ?? 0),
                'zone_id' => (int) ($_POST['zone_id'] ?? 0),
                'date_debut' => $date_start,
                'date_fin' => $date_end,
            ];

            if ($action === 'create') {
                $statement = $pdo->prepare(<<<'SQL'
                    INSERT INTO autorisation (utilisateur_id, zone_id, date_debut, date_fin)
                    VALUES (:utilisateur_id, :zone_id, COALESCE(:date_debut, CURRENT_TIMESTAMP), :date_fin)
                SQL);
                $message = 'Autorisation ajoutée.';
            } else {
                $statement = $pdo->prepare(<<<'SQL'
                    UPDATE autorisation
                    SET utilisateur_id = :utilisateur_id,
                        zone_id = :zone_id,
                        date_debut = COALESCE(:date_debut, date_debut),
                        date_fin = :date_fin
                    WHERE id = :id
                SQL);
                $values['id'] = (int) ($_POST['id'] ?? 0);
                $message = 'Autorisation modifiée.';
            }

            $statement->bindValue(':utilisateur_id', $values['utilisateur_id'], PDO::PARAM_INT);
            $statement->bindValue(':zone_id', $values['zone_id'], PDO::PARAM_INT);
            $statement->bindValue(':date_debut', $values['date_debut'], $values['date_debut'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $statement->bindValue(':date_fin', $values['date_fin'], $values['date_fin'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            if ($action === 'update') {
                $statement->bindValue(':id', $values['id'], PDO::PARAM_INT);
            }
            $statement->execute();
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM autorisation WHERE id = :id');
            $statement->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            $message = 'Autorisation retirée.';
        }
    } catch (PDOException $exception) {
        $message = 'Opération impossible. Vérifiez les dates et les associations.';
        $message_type = 'error';
    }
}

$users = $pdo->query('SELECT id, matricule FROM utilisateur ORDER BY matricule')->fetchAll();
$zones = $pdo->query('SELECT id, nom FROM zone ORDER BY nom')->fetchAll();

$search_user = trim((string) ($_GET['utilisateur'] ?? ''));
$search_zone = trim((string) ($_GET['zone'] ?? ''));
$period_from = trim((string) ($_GET['de'] ?? ''));
$period_to = trim((string) ($_GET['a'] ?? ''));
$page_number = max(1, (int) ($_GET['page_num'] ?? 1));
$items_per_page = 20;

$where = [];
$parameters = [];
if ($search_user !== '') {
    $where[] = 'utilisateur.matricule ILIKE :utilisateur';
    $parameters['utilisateur'] = '%' . $search_user . '%';
}
if ($search_zone !== '') {
    $where[] = 'zone.nom ILIKE :zone';
    $parameters['zone'] = '%' . $search_zone . '%';
}
if ($period_from !== '') {
    $where[] = '(autorisation.date_fin IS NULL OR autorisation.date_fin >= CAST(:periode_debut AS timestamp))';
    $parameters['periode_debut'] = str_replace('T', ' ', $period_from);
}
if ($period_to !== '') {
    $where[] = 'autorisation.date_debut <= CAST(:periode_fin AS timestamp)';
    $parameters['periode_fin'] = str_replace('T', ' ', $period_to);
}

$where_sql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
$joins_sql = <<<'SQL'
    FROM autorisation
    INNER JOIN utilisateur ON utilisateur.id = autorisation.utilisateur_id
    LEFT JOIN badge ON badge.utilisateur_id = utilisateur.id
    INNER JOIN zone ON zone.id = autorisation.zone_id
SQL;

$count_statement = $pdo->prepare("SELECT COUNT(*) {$joins_sql} {$where_sql}");
$count_statement->execute($parameters);
$total_permissions = (int) $count_statement->fetchColumn();
$total_pages = max(1, (int) ceil($total_permissions / $items_per_page));
$page_number = min($page_number, $total_pages);
$offset = ($page_number - 1) * $items_per_page;

$permissions_statement = $pdo->prepare(<<<SQL
    SELECT
        autorisation.id,
        autorisation.utilisateur_id,
        autorisation.zone_id,
        autorisation.date_debut,
        autorisation.date_fin,
        utilisateur.matricule,
        badge.numero_serie,
        zone.nom AS zone_nom
    {$joins_sql}
    {$where_sql}
    ORDER BY autorisation.date_debut DESC, autorisation.id DESC
    LIMIT :limit OFFSET :offset
SQL);
foreach ($parameters as $name => $value) {
    $permissions_statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$permissions_statement->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$permissions_statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$permissions_statement->execute();
$permissions = $permissions_statement->fetchAll();

$query_parameters = [
    'page' => 'droits',
    'utilisateur' => $search_user,
    'zone' => $search_zone,
    'de' => $period_from,
    'a' => $period_to,
];
$build_url = static function (array $parameters): string {
    return 'index.php?' . http_build_query(array_filter($parameters, static fn ($value): bool => $value !== '' && $value !== null));
};
?>

<main class="page-content management-page rights-page">
    <section class="access-heading management-heading" aria-labelledby="rights-title">
        <div>
            <p class="eyebrow">TABLE AUTORISATION</p>
            <h1 id="rights-title">Droits <span>d'accès</span></h1>
            <p class="intro">Ajoutez, modifiez ou retirez les autorisations accordées aux utilisateurs.</p>
        </div>
        <div class="demo-status"><span></span> Données Supabase</div>
    </section>

    <?php if ($message !== ''): ?>
        <p class="form-message <?= $message_type === 'error' ? 'is-error' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section class="dashboard-section management-form-section" aria-labelledby="new-right-title">
        <p class="eyebrow">NOUVELLE AUTORISATION</p>
        <h2 id="new-right-title">Accorder un accès</h2>
        <form class="rights-form" method="post" action="index.php?page=droits">
            <input type="hidden" name="action" value="create">
            <label>Utilisateur<select name="utilisateur_id" required><option value="">Choisir un utilisateur</option><?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>"><?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
            <label>Zone<select name="zone_id" required><option value="">Choisir une zone</option><?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
            <label>Début <input type="datetime-local" name="date_debut"></label>
            <label>Fin <input type="datetime-local" name="date_fin"></label>
            <button class="button" type="submit">Ajouter le droit</button>
        </form>
    </section>

    <section class="dashboard-section event-filters rights-filters" aria-labelledby="rights-filters-title">
        <div class="section-heading"><div><p class="eyebrow">RECHERCHE</p><h2 id="rights-filters-title">Filtrer les autorisations</h2></div><a class="clear-filters" href="index.php?page=droits">Réinitialiser</a></div>
        <form class="event-filter-form" method="get" action="index.php">
            <input type="hidden" name="page" value="droits">
            <label><span>Utilisateur</span><input type="search" name="utilisateur" value="<?= htmlspecialchars($search_user, ENT_QUOTES, 'UTF-8') ?>" placeholder="Matricule"></label>
            <label><span>Zone</span><input type="search" name="zone" value="<?= htmlspecialchars($search_zone, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom de zone"></label>
            <label><span>Période de</span><input type="datetime-local" name="de" value="<?= htmlspecialchars($period_from, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label><span>Période à</span><input type="datetime-local" name="a" value="<?= htmlspecialchars($period_to, ENT_QUOTES, 'UTF-8') ?>"></label>
            <button class="button filter-button" type="submit">Rechercher</button>
        </form>
    </section>

    <section class="dashboard-section management-table-section" aria-labelledby="rights-list-title">
        <div class="section-heading"><div><p class="eyebrow"><?= $total_permissions ?> RÉSULTAT<?= $total_permissions > 1 ? 'S' : '' ?> / PAGE <?= $page_number ?> SUR <?= $total_pages ?></p><h2 id="rights-list-title">Autorisations enregistrées</h2></div><span class="section-count">20 lignes maximum</span></div>
        <div class="table-wrapper">
            <table class="data-table management-table rights-table">
                <thead><tr><th>Utilisateur</th><th>Badge</th><th>Zone</th><th>Début</th><th>Fin</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($permissions === []): ?><tr><td class="empty-table" colspan="6">Aucune autorisation trouvée.</td></tr><?php endif; ?>
                    <?php foreach ($permissions as $permission): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($permission['matricule'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= $permission['numero_serie'] !== null ? '<code>' . htmlspecialchars($permission['numero_serie'], ENT_QUOTES, 'UTF-8') . '</code>' : '<span class="table-muted">Pas de badge</span>' ?></td>
                            <td><?= htmlspecialchars($permission['zone_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($permission['date_debut'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $permission['date_fin'] ? htmlspecialchars($permission['date_fin'], ENT_QUOTES, 'UTF-8') : 'Sans expiration' ?></td>
                            <td class="row-actions">
                                <details><summary>Modifier</summary>
                                    <form class="inline-form" method="post" action="index.php?page=droits">
                                        <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $permission['id'] ?>">
                                        <label>Utilisateur<select name="utilisateur_id" required><?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>" <?= (int) $user['id'] === (int) $permission['utilisateur_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
                                        <label>Zone<select name="zone_id" required><?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>" <?= (int) $zone['id'] === (int) $permission['zone_id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
                                        <label>Début <input type="datetime-local" name="date_debut" value="<?= htmlspecialchars(date('Y-m-d\\TH:i', strtotime($permission['date_debut'])), ENT_QUOTES, 'UTF-8') ?>"></label>
                                        <label>Fin <input type="datetime-local" name="date_fin" value="<?= $permission['date_fin'] ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($permission['date_fin'])), ENT_QUOTES, 'UTF-8') : '' ?>"></label>
                                        <button class="button small-button" type="submit">Enregistrer</button>
                                    </form>
                                </details>
                                <form method="post" action="index.php?page=droits" data-confirm="Retirer cette autorisation ?"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $permission['id'] ?>"><button class="danger-button" type="submit">Retirer</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?><nav class="pagination" aria-label="Pagination des autorisations"><?php for ($page_index = 1; $page_index <= $total_pages; $page_index++): ?><a class="pagination-link <?= $page_index === $page_number ? 'is-current' : '' ?>" href="<?= htmlspecialchars($build_url(array_merge($query_parameters, ['page_num' => $page_index])), ENT_QUOTES, 'UTF-8') ?>"><?= $page_index ?></a><?php endfor; ?></nav><?php endif; ?>
    </section>
</main>
