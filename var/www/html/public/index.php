<?php
declare(strict_types=1);

$page = $_GET['page'] ?? 'home';

require_once __DIR__ . '/../pages/header.php';

// AI_HANDOFF / TODO_BACKEND : initialiser session, controles d'acces et traitements
// POST des tickets AVANT le header ci-dessus (sortie HTML), puis rediriger.
// Les routes tickets ci-dessous exposent actuellement une demonstration publique.

switch ($page) {
    case 'my-tickets':
        require_once __DIR__ . '/../pages/tickets/mine.php';
        break;

    case 'ticket-support':
        require_once __DIR__ . '/../pages/tickets/support.php';
        break;

    case 'ticket-create':
        require_once __DIR__ . '/../pages/tickets/create.php';
        break;

    case 'dashboard':
        require_once __DIR__ . '/../pages/dashboard.php';
        break;

    case 'zones':
        require_once __DIR__ . '/../pages/zones.php';
        break;

    case 'permissions':
        require_once __DIR__ . '/../pages/permissions.php';
        break;

    case 'droits':
        require_once __DIR__ . '/../pages/droits.php';
        break;

    case 'users':
        require_once __DIR__ . '/../pages/users.php';
        break;

    case 'badges':
        require_once __DIR__ . '/../pages/badges.php';
        break;

    case 'badgeuses':
        require_once __DIR__ . '/../pages/badgeuses.php';
        break;

    case 'events':
        require_once __DIR__ . '/../pages/events.php';
        break;

    case 'home':
    default:
        ?>
        <main class="page-content">
            <section class="hero" aria-labelledby="page-title">
                <p class="eyebrow">Workshop EPSI</p>
                <h1 id="page-title">Projet <span>YGGDRASIL</span></h1>
                <p class="intro">Bienvenue sur notre espace de workshop.</p>
                <a class="button" href="index.php?page=dashboard">Accéder au dashboard</a>
            </section>

            <section class="content-section" id="presentation" aria-labelledby="presentation-title">
                <p class="eyebrow">À propos</p>
                <h2 id="presentation-title">Un espace de gestion du pôle sécurité du vaisseau mère</h2>
                <p>Gestion d'accès aux différents secteurs, aux différents pôles, gestion de sécurité des secteurs et des droits d'accès utilisateurs</p>
            </section>
        </main>
        <?php
        break;
}

require_once __DIR__ . '/../pages/footer.php';
?>
