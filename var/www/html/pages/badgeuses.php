<?php
require_once __DIR__ . '/updateZone.php';

updateZonesFromCsv();

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $statement = $pdo->prepare(<<<'SQL'
                INSERT INTO badgeuse (code, zone_id, etat)
                VALUES (:code, :zone_id, :etat)
            SQL);
            $statement->bindValue(':code', trim((string) ($_POST['code'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':zone_id', (int) ($_POST['zone_id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->execute();
            $message = 'Badgeuse créée.';
        } elseif ($action === 'update') {
            $statement = $pdo->prepare(<<<'SQL'
                UPDATE badgeuse
                SET code = :code, zone_id = :zone_id, etat = :etat
                WHERE id = :id
            SQL);
            $statement->bindValue(':id', (int) ($_POST['id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':code', trim((string) ($_POST['code'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':zone_id', (int) ($_POST['zone_id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->execute();
            $message = 'Badgeuse modifiée.';
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM badgeuse WHERE id = :id');
            $statement->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            $message = 'Badgeuse supprimée.';
        }
    } catch (PDOException $exception) {
        $message = 'Opération impossible. Vérifiez le code et la zone associée.';
        $message_type = 'error';
    }
}

$zones = $pdo->query('SELECT id, nom FROM zone ORDER BY nom')->fetchAll();
$badgeuses = $pdo->query(<<<'SQL'
    SELECT badgeuse.id, badgeuse.code, badgeuse.zone_id, badgeuse.etat, zone.nom AS zone_nom
    FROM badgeuse
    INNER JOIN zone ON zone.id = badgeuse.zone_id
    ORDER BY badgeuse.id
SQL
)->fetchAll();
?>

<main class="page-content management-page">
    <section class="access-heading management-heading" aria-labelledby="badgeuses-title">
        <div>
            <p class="eyebrow">TABLE BADGEUSE</p>
            <h1 id="badgeuses-title">Gestion des <span>badgeuses</span></h1>
            <p class="intro">Créez, modifiez et supprimez les lecteurs associés aux zones.</p>
        </div>
        <div class="demo-status"><span></span> Données Supabase</div>
    </section>

    <?php if ($message !== ''): ?>
        <p class="form-message <?= $message_type === 'error' ? 'is-error' : '' ?>" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <section class="dashboard-section management-form-section" aria-labelledby="new-badgeuse-title">
        <p class="eyebrow">NOUVELLE BADGEUSE</p>
        <h2 id="new-badgeuse-title">Créer une badgeuse</h2>
        <form class="management-form" method="post" action="index.php?page=badgeuses">
            <input type="hidden" name="action" value="create">
            <label>Code <input type="text" name="code" maxlength="50" required></label>
            <label>Zone
                <select name="zone_id" required>
                    <option value="">Choisir une zone</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="checkbox-field"><input type="checkbox" name="etat" checked> Badgeuse active</label>
            <button class="button" type="submit">Créer la badgeuse</button>
        </form>
    </section>

    <section class="dashboard-section management-table-section" aria-labelledby="badgeuses-list-title">
        <div class="section-heading">
            <div><p class="eyebrow">BADGEUSES ENREGISTRÉES</p><h2 id="badgeuses-list-title">Liste des badgeuses</h2></div>
            <span class="section-count"><?= count($badgeuses) ?> badgeuse<?= count($badgeuses) > 1 ? 's' : '' ?></span>
        </div>
        <div class="table-wrapper">
            <table class="data-table management-table">
                <thead><tr><th>ID</th><th>Code</th><th>Zone</th><th>État</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($badgeuses === []): ?><tr><td class="empty-table" colspan="5">Aucune badgeuse trouvée.</td></tr><?php endif; ?>
                    <?php foreach ($badgeuses as $badgeuse): ?>
                        <tr>
                            <td><?= (int) $badgeuse['id'] ?></td>
                            <td><code><?= htmlspecialchars($badgeuse['code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars($badgeuse['zone_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="result <?= $badgeuse['etat'] ? 'is-success' : 'is-danger' ?>"><?= $badgeuse['etat'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="row-actions">
                                <details><summary>Modifier</summary>
                                    <form class="inline-form" method="post" action="index.php?page=badgeuses">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int) $badgeuse['id'] ?>">
                                        <input type="text" name="code" value="<?= htmlspecialchars($badgeuse['code'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>
                                        <select name="zone_id" required>
                                            <?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>" <?= (int) $zone['id'] === (int) $badgeuse['zone_id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                        </select>
                                        <label class="checkbox-field"><input type="checkbox" name="etat" <?= $badgeuse['etat'] ? 'checked' : '' ?>> Active</label>
                                        <button class="button small-button" type="submit">Enregistrer</button>
                                    </form>
                                </details>
                                <form method="post" action="index.php?page=badgeuses" data-confirm="Supprimer cette badgeuse ?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $badgeuse['id'] ?>">
                                    <button class="danger-button" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>