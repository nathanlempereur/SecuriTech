<?php
/* AI_HANDOFF / TEMP_DEMO : les tickets personnels sont ceux du demandeur demo-local.
 * TODO_AUTH : exiger une session et charger uniquement les tickets dont
 * utilisateur_id correspond a l'identite connectee, avec controle serveur.
 * TODO_BACKEND : rendre le suivi et l'historique depuis la base, avec pagination.
 * Ne pas considerer le filtrage JavaScript comme une protection des donnees.
 */
?>
<main class="page-content ticket-page">
    <section class="access-heading" aria-labelledby="my-tickets-title">
        <div>
            <p class="eyebrow">Support utilisateur</p>
            <h1 id="my-tickets-title">Mes <span>tickets</span></h1>
            <p class="intro">Suivez la prise en charge de vos demandes et les dernières évolutions.</p>
        </div>
        <div class="demo-status"><span></span> Mode démonstration</div>
    </section>
    <p class="ticket-help">Sans connexion, cette page affiche les tickets du demandeur de démonstration de ce navigateur ; les exemples des autres utilisateurs restent dans la file support. Le suivi personnel par compte sera disponible avec l'authentification.</p>
    <div class="ticket-actions">
        <a class="button" href="index.php?page=ticket-create">Créer un ticket</a>
        <label for="my-tickets-status">Afficher</label>
        <select id="my-tickets-status">
            <option value="">Tous les tickets</option>
            <option value="ouvert">Ouverts</option>
            <option value="en_cours">En cours</option>
            <option value="resolu">Résolus</option>
            <option value="ferme">Fermés</option>
        </select>
    </div>
    <p id="ticket-feedback" role="status"></p>
    <p class="ticket-help">Les demandes les plus récemment mises à jour apparaissent en premier.</p>
    <div id="my-tickets"></div>
    <noscript><p>Activez JavaScript pour consulter vos tickets de démonstration.</p></noscript>
</main>
<script src="js/tickets.js" defer></script>
