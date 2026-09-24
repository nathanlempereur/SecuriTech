<?php
require_once __DIR__ . '/../config/db.php';

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $statement = $pdo->prepare(<<<'SQL'
                INSERT INTO badge (utilisateur_id, numero_serie, etat, date_expiration)
                VALUES (:utilisateur_id, :numero_serie, :etat, :date_expiration)
            SQL);
            $statement->bindValue(':utilisateur_id', (int) ($_POST['utilisateur_id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':numero_serie', trim((string) ($_POST['numero_serie'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->bindValue(':date_expiration', ($_POST['date_expiration'] ?? '') !== '' ? $_POST['date_expiration'] : null, ($_POST['date_expiration'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $statement->execute();
            $message = 'Badge créé et associé.';
        } elseif ($action === 'update') {
            $statement = $pdo->prepare(<<<'SQL'
                UPDATE badge
                SET utilisateur_id = :utilisateur_id, numero_serie = :numero_serie,
                    etat = :etat, date_expiration = :date_expiration
                WHERE id = :id
            SQL);
            $statement->bindValue(':id', (int) ($_POST['id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':utilisateur_id', (int) ($_POST['utilisateur_id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':numero_serie', trim((string) ($_POST['numero_serie'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->bindValue(':date_expiration', ($_POST['date_expiration'] ?? '') !== '' ? $_POST['date_expiration'] : null, ($_POST['date_expiration'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $statement->execute();
            $message = 'Badge modifié.';
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM badge WHERE id = :id');
            $statement->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            $message = 'Badge supprimé.';
        }
    } catch (PDOException $exception) {
        $message = 'Opération impossible. Vérifiez le numéro de série et l’association utilisateur.';
        $message_type = 'error';
    }
}

$users = $pdo->query('SELECT id, matricule FROM utilisateur ORDER BY matricule')->fetchAll();
$badges = $pdo->query(<<<'SQL'
    SELECT badge.id, badge.numero_serie, badge.etat, badge.date_expiration,
           badge.utilisateur_id, utilisateur.matricule
    FROM badge
    INNER JOIN utilisateur ON utilisateur.id = badge.utilisateur_id
    ORDER BY badge.id
SQL
)->fetchAll();
?>

<main class="page-content management-page">
    <section class="access-heading management-heading" aria-labelledby="badges-title">
        <div>
            <p class="eyebrow">TABLE BADGE</p>
            <h1 id="badges-title">Gestion des <span>badges</span></h1>
            <p class="intro">Créez les badges et associez-les aux utilisateurs existants.</p>
        </div>
        <div class="demo-status"><span></span> Données Supabase</div>
    </section>

    <?php if ($message !== ''): ?>
        <p class="form-message <?= $message_type === 'error' ? 'is-error' : '' ?>" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <section class="dashboard-section management-form-section" aria-labelledby="new-badge-title">
        <p class="eyebrow">NOUVEAU BADGE</p>
        <h2 id="new-badge-title">Créer et associer un badge</h2>
        <form class="badge-form" method="post" action="index.php?page=badges">
            <input type="hidden" name="action" value="create">
            <label>Numéro de série <input type="text" name="numero_serie" maxlength="100" required></label>
            <label>Utilisateur
                <select name="utilisateur_id" required>
                    <option value="">Choisir un utilisateur</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>"><?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date d'expiration <input type="datetime-local" name="date_expiration"></label>
            <label class="checkbox-field"><input type="checkbox" name="etat" checked> Badge actif</label>
            <button class="button" type="submit">Créer le badge</button>
        </form>
    </section>

    <section class="dashboard-section management-table-section" aria-labelledby="badges-list-title">
        <div class="section-heading">
            <div><p class="eyebrow">BADGES ENREGISTRÉS</p><h2 id="badges-list-title">Liste des badges</h2></div>
            <span class="section-count"><?= count($badges) ?> badge<?= count($badges) > 1 ? 's' : '' ?></span>
        </div>
        <div class="table-wrapper">
            <table class="data-table management-table">
                <thead><tr><th>Numéro de série</th><th>Utilisateur</th><th>État</th><th>Expiration</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($badges === []): ?><tr><td class="empty-table" colspan="5">Aucun badge trouvé.</td></tr><?php endif; ?>
                    <?php foreach ($badges as $badge): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($badge['numero_serie'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars($badge['matricule'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="result <?= $badge['etat'] ? 'is-success' : 'is-danger' ?>"><?= $badge['etat'] ? 'Actif' : 'Inactif' ?></span></td>
                            <td><?= $badge['date_expiration'] ? htmlspecialchars($badge['date_expiration'], ENT_QUOTES, 'UTF-8') : 'Aucune' ?></td>
                            <td class="row-actions">
                                <details><summary>Modifier</summary>
                                    <form class="inline-form" method="post" action="index.php?page=badges">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int) $badge['id'] ?>">
                                        <input type="text" name="numero_serie" value="<?= htmlspecialchars($badge['numero_serie'], ENT_QUOTES, 'UTF-8') ?>" maxlength="100" required>
                                        <select name="utilisateur_id" required>
                                            <?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>" <?= (int) $user['id'] === (int) $badge['utilisateur_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                        </select>
                                        <label class="checkbox-field"><input type="checkbox" name="etat" <?= $badge['etat'] ? 'checked' : '' ?>> Actif</label>
                                        <button class="button small-button" type="submit">Enregistrer</button>
                                    </form>
                                </details>
                                <form method="post" action="index.php?page=badges" data-confirm="Supprimer ce badge ?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $badge['id'] ?>">
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
