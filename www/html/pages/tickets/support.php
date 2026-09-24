<?php
/* AI_HANDOFF / TEMP_DEMO : page publique de simulation, aucun role support verifie.
 * TODO_AUTH : proteger la lecture et chaque mutation par un controle de role serveur.
 * TODO_BACKEND : charger la file depuis la base et enregistrer priorite, statut,
 * signalement urgent et historique dans une meme transaction authentifiee.
 * KEEP_DOMAIN : retirer le signalement ne doit pas effacer la priorite support.
 */
?>
<main class="page-content ticket-page">
    <section class="access-heading" aria-labelledby="support-title">
        <div>
            <p class="eyebrow">Support utilisateur</p>
            <h1 id="support-title">Gestion des <span>tickets</span></h1>
            <p class="intro">Examinez les urgences signalées, définissez les priorités et suivez le traitement.</p>
        </div>
        <div class="demo-status"><span></span> Mode démonstration</div>
    </section>
    <p class="ticket-help">Espace de démonstration sans connexion : les modifications restent dans ce navigateur, sans envoi au support. L'accès aux fonctions d'agent n'est pas encore protégé.</p>
    <div class="ticket-actions">
        <a class="button" href="index.php?page=ticket-create">Créer un ticket</a>
        <label><input id="support-urgent-only" type="checkbox"> Urgences à examiner uniquement</label>
    </div>
    <form id="support-filters" class="ticket-form support-filters" role="search">
        <label>Rechercher<input id="support-search" type="search" placeholder="Objet, référence, nom ou matricule"></label>
        <label>Demandeur<select id="support-user"><option value="">Tous les demandeurs</option></select></label>
        <label>Statut<select id="support-status"><option value="">Tous les statuts</option></select></label>
        <label>Priorité<select id="support-priority"><option value="all">Toutes les priorités</option></select></label>
        <label>Créé depuis le<input id="support-from" type="date"></label>
        <label>Créé jusqu'au<input id="support-to" type="date"></label>
        <button type="reset" class="button">Réinitialiser les filtres</button>
    </form>
    <div class="ticket-actions"><button type="button" id="support-examples" class="button">Ajouter des exemples multi-utilisateurs</button></div>
    <p id="support-count" role="status"></p>
    <p id="support-feedback" role="status"></p>
    <p class="ticket-help">Ordre : urgences signalées, tickets à qualifier, puis priorité définie par le support. À niveau égal, les plus anciens apparaissent en premier.</p>
    <div class="table-wrapper"><table class="data-table support-table">
        <caption>Tickets correspondant aux filtres — ouvrez une fiche pour consulter le détail.</caption>
        <thead><tr><th>Ticket</th><th>Demandeur</th><th>Statut / priorité</th><th>Création</th><th>Mise à jour</th><th>Notes</th></tr></thead>
        <tbody id="support-rows"></tbody>
    </table></div>
    <div id="support-tickets"></div>
    <noscript><p>Activez JavaScript pour afficher les tickets de démonstration.</p></noscript>
</main>
<script src="js/tickets.js" defer></script>
