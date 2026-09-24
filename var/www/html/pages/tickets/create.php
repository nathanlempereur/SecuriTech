<?php
/* AI_HANDOFF / TEMP_DEMO : formulaire pilote par public/js/tickets.js.
 * TODO_BACKEND : creation via POST valide cote serveur, CSRF et redirection.
 * Deduirе utilisateur_id de la session ; accepter une zone facultative valide.
 * Le demandeur peut signaler une urgence, jamais imposer la priorite support.
 * Remplacer la liste fictive des zones par les zones de la base.
 */
?>
<main class="page-content ticket-page">
    <section class="access-heading" aria-labelledby="ticket-title">
        <div>
            <p class="eyebrow">Support utilisateur</p>
            <h1 id="ticket-title">Créer un <span>ticket</span></h1>
            <p class="intro">Un problème de badge ou d'accès ? Décrivez votre demande pour préparer sa prise en charge.</p>
        </div>
        <div class="demo-status"><span></span> Mode démonstration</div>
    </section>

    <div class="ticket-layout">
        <section class="dashboard-section" aria-labelledby="ticket-form-title">
            <h2 id="ticket-form-title">Votre demande</h2>
            <p class="ticket-help" id="ticket-demo-notice">Démonstration : les tickets sont conservés uniquement dans ce navigateur. Aucun ticket n'est envoyé à un service de support. N'utilisez pas de données sensibles.</p>
            <form id="ticket-form" class="ticket-form" aria-describedby="ticket-demo-notice">
                <label for="ticket-titre">Objet <span aria-hidden="true">*</span></label>
                <input id="ticket-titre" name="titre" type="text" maxlength="255" required placeholder="Ex. Mon badge ne permet plus d'accéder à l'atelier">

                <label for="ticket-zone">Zone concernée (facultatif)</label>
                <select id="ticket-zone" name="zone_id" aria-describedby="ticket-zone-help">
                    <option value="">Aucune zone concernée</option>
                </select>
                <p class="ticket-help" id="ticket-zone-help">Sélectionnez une zone si votre demande concerne un lieu précis. Les zones proposées sont celles de la démonstration.</p>

                <label class="ticket-checkbox" for="ticket-urgence">
                    <input id="ticket-urgence" name="a_prioriser_rapidement" type="checkbox">
                    Cette demande nécessite un examen urgent
                </label>
                <p class="ticket-help">Signalez une urgence réelle et expliquez son impact dans la description. Le support examinera ce signalement en premier et définira la priorité.</p>

                <label for="ticket-description">Description <span aria-hidden="true">*</span></label>
                <textarea id="ticket-description" name="description" rows="8" required aria-describedby="ticket-description-help" placeholder="Que s'est-il passé ? Où et quand ? Quel message avez-vous rencontré ?"></textarea>
                <p class="ticket-help" id="ticket-description-help">Précisez la zone concernée, le résultat attendu et les éventuelles tentatives déjà effectuées.</p>

                <div class="ticket-actions">
                    <button class="button" id="ticket-preview-button" type="submit" disabled>Créer le ticket de démonstration</button>
                    <span class="ticket-help">* Champs obligatoires</span>
                </div>
                <noscript><p>Activez JavaScript pour créer un ticket de démonstration.</p></noscript>
            </form>
        </section>

        <aside class="dashboard-section ticket-guidance" aria-labelledby="ticket-guidance-title">
            <p class="eyebrow">Bien décrire le problème</p>
            <h2 id="ticket-guidance-title">Les détails utiles</h2>
            <ul>
                <li>La zone ou le badge concerné.</li>
                <li>La date et l'heure de l'incident.</li>
                <li>Le message affiché ou le refus rencontré.</li>
                <li>L'impact sur votre activité.</li>
            </ul>
            <p class="ticket-help">Lors de l'activation du service, votre ticket sera associé à votre compte connecté et ouvert en attente d'attribution au support.</p>
        </aside>
    </div>

    <p id="ticket-feedback" role="status"></p>
    <a href="index.php?page=my-tickets">Suivre mes tickets →</a>
</main>
<script src="js/tickets.js" defer></script>
