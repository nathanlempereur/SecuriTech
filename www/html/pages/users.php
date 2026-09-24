<?php
require_once __DIR__ . '/../config/db.php';

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $statement = $pdo->prepare(
                'INSERT INTO utilisateur (matricule, etat) VALUES (:matricule, :etat)'
            );
            $statement->bindValue(':matricule', trim((string) ($_POST['matricule'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->execute();
            $message = 'Utilisateur créé.';
        } elseif ($action === 'update') {
            $statement = $pdo->prepare(
                'UPDATE utilisateur SET matricule = :matricule, etat = :etat WHERE id = :id'
            );
            $statement->bindValue(':id', (int) ($_POST['id'] ?? 0), PDO::PARAM_INT);
            $statement->bindValue(':matricule', trim((string) ($_POST['matricule'] ?? '')), PDO::PARAM_STR);
            $statement->bindValue(':etat', isset($_POST['etat']), PDO::PARAM_BOOL);
            $statement->execute();
            $message = 'Utilisateur modifié.';
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM utilisateur WHERE id = :id');
            $statement->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            $message = 'Utilisateur supprimé.';
        }
    } catch (PDOException $exception) {
        $message = 'Opération impossible. Vérifiez le matricule et les badges associés.';
        $message_type = 'error';
    }
}

$search = trim((string) ($_GET['recherche'] ?? ''));
$users_statement = $pdo->prepare(<<<'SQL'
    SELECT
        utilisateur.id,
        utilisateur.matricule,
        utilisateur.etat,
        badge.numero_serie
    FROM utilisateur
    LEFT JOIN badge ON badge.utilisateur_id = utilisateur.id
    WHERE utilisateur.matricule ILIKE :search
    ORDER BY utilisateur.id
SQL
);
$users_statement->execute(['search' => '%' . $search . '%']);
$users = $users_statement->fetchAll();
?>

<main class="page-content management-page">
    <section class="access-heading management-heading" aria-labelledby="users-title">
        <div>
            <p class="eyebrow">TABLE UTILISATEUR</p>
            <h1 id="users-title">Gestion des <span>utilisateurs</span></h1>
            <p class="intro">Créez, modifiez, recherchez et supprimez les utilisateurs du système.</p>
        </div>
        <div class="demo-status"><span></span> Données Supabase</div>
    </section>

    <?php if ($message !== ''): ?>
        <p class="form-message <?= $message_type === 'error' ? 'is-error' : '' ?>" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <section class="management-grid">
        <section class="dashboard-section management-form-section" aria-labelledby="new-user-title">
            <p class="eyebrow">NOUVEAU PROFIL</p>
            <h2 id="new-user-title">Créer un utilisateur</h2>
            <form class="management-form" method="post" action="index.php?page=users">
                <input type="hidden" name="action" value="create">
                <label>Matricule <input type="text" name="matricule" maxlength="50" required></label>
                <label class="checkbox-field"><input type="checkbox" name="etat" checked> Utilisateur actif</label>
                <button class="button" type="submit">Créer l'utilisateur</button>
            </form>
        </section>

        <section class="dashboard-section management-form-section" aria-labelledby="search-user-title">
            <p class="eyebrow">RECHERCHE</p>
            <h2 id="search-user-title">Trouver un utilisateur</h2>
            <form class="management-search" method="get" action="index.php">
                <input type="hidden" name="page" value="users">
                <input type="search" name="recherche" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher par matricule">
                <button class="button" type="submit">Rechercher</button>
                <a class="clear-filters" href="index.php?page=users">Réinitialiser</a>
            </form>
        </section>
    </section>

    <section class="dashboard-section management-table-section" aria-labelledby="users-list-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">RÉSULTATS</p>
                <h2 id="users-list-title">Utilisateurs enregistrés</h2>
            </div>
            <span class="section-count"><?= count($users) ?> résultat<?= count($users) > 1 ? 's' : '' ?></span>
        </div>
        <div class="table-wrapper">
            <table class="data-table management-table">
                <thead><tr><th>Matricule</th><th>État</th><th>Badge associé</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($users === []): ?>
                        <tr><td class="empty-table" colspan="4">Aucun utilisateur trouvé.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><span class="result <?= $user['etat'] ? 'is-success' : 'is-danger' ?>"><?= $user['etat'] ? 'Actif' : 'Suspendu' ?></span></td>
                            <td><?= $user['numero_serie'] !== null ? htmlspecialchars($user['numero_serie'], ENT_QUOTES, 'UTF-8') : '<span class="table-muted">Aucun badge</span>' ?></td>
                            <td class="row-actions">
                                <details><summary>Modifier</summary>
                                    <form class="inline-form" method="post" action="index.php?page=users">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <input type="text" name="matricule" value="<?= htmlspecialchars($user['matricule'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>
                                        <label class="checkbox-field"><input type="checkbox" name="etat" <?= $user['etat'] ? 'checked' : '' ?>> Actif</label>
                                        <button class="button small-button" type="submit">Enregistrer</button>
                                    </form>
                                </details>
                                <form method="post" action="index.php?page=users" data-confirm="Supprimer cet utilisateur ?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
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
