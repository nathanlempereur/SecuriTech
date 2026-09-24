<?php

// Connexion à la base de données
require_once __DIR__ . '/../config/db.php';

// Récupération des utilisateurs et de leurs badges
$users = $pdo->query(<<<'SQL'
    SELECT
        utilisateur.id,
        utilisateur.matricule,
        utilisateur.etat AS utilisateur_etat,
        badge.numero_serie,
        badge.etat AS badge_etat
    FROM utilisateur
    LEFT JOIN badge
        ON badge.utilisateur_id = utilisateur.id
    ORDER BY utilisateur.id
SQL
)->fetchAll();

?>

<main class="page-content permissions-page">

    <!-- En-tête de la page -->
    <section
        class="access-heading permissions-heading"
        aria-labelledby="permissions-title"
    >
        <div>
            <p class="eyebrow">
                UTILISATEUR / BADGE
            </p>
            <h1 id="permissions-title">
                Gestion des <span>utilisateurs</span>
            </h1>
            <p class="intro">
                Consultez les matricules, les badges associés et leur état actuel.
            </p>
        </div>
        <div class="demo-status">
            <span></span>
            Données Supabase
        </div>
    </section>


    <!-- Liste des utilisateurs -->
    <section
        class="dashboard-section users-table-section"
        aria-labelledby="users-title"
    >
        <div class="section-heading">
            <div>
                <p class="eyebrow">
                    TABLE UTILISATEUR
                </p>
                <h2 id="users-title">
                    Liste des utilisateurs
                </h2>
            </div>
            <!-- Nombre d'utilisateurs -->
            <span class="section-count">
                <?= count($users) ?>
                profil<?= count($users) > 1 ? 's' : '' ?>
            </span>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="table-wrapper">
            <table class="data-table users-data-table">
                <!-- En-tête du tableau -->
                <thead>
                    <tr>
                        <th>Matricule utilisateur</th>
                        <th>État utilisateur</th>
                        <th>Numéro de série du badge</th>
                        <th>État du badge</th>
                    </tr>
                </thead>

                <!-- Données du tableau -->
                <tbody>
                    <!-- Vérifie si aucun utilisateur n'a été trouvé -->
                    <?php if ($users === []): ?>
                        <tr>
                            <td class="empty-table" colspan="4">
                                Aucun utilisateur trouvé.
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Parcourt tous les utilisateurs -->
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <!-- Affiche le matricule -->
                                <td>
                                    <code>
                                        <?= htmlspecialchars(
                                            $user['matricule'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </code>
                                </td>

                                <!-- Affiche l'état de l'utilisateur -->
                                <td>
                                    <?php if ((bool) $user['utilisateur_etat']): ?>
                                        <span class="result is-success">
                                            Actif
                                        </span>
                                    <?php else: ?>
                                        <span class="result is-danger">
                                            Suspendu
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Affiche le numéro du badge -->
                                <td>
                                    <?php if ($user['numero_serie'] !== null): ?>
                                        <code>
                                            <?= htmlspecialchars(
                                                $user['numero_serie'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="table-muted">
                                            Aucun badge associé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Affiche l'état du badge -->
                                <td>
                                    <?php if ($user['badge_etat'] === null): ?>
                                        <span class="table-muted">
                                            Aucun badge
                                        </span>
                                    <?php elseif ((bool) $user['badge_etat']): ?>
                                        <span class="result is-success">
                                            Actif
                                        </span>
                                    <?php else: ?>
                                        <span class="result is-danger">
                                            Inactif
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
