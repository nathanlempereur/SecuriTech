/**
 * AI_HANDOFF / TEMP_DEMO — lire avant de raccorder ce module.
 * Ce fichier simule le backend, sans authentification ni isolation entre utilisateurs.
 * TODO_BACKEND : remplacer localStorage, les identifiants DEMO, les dates et les
 * mutations locales par des traitements PHP et une persistance PostgreSQL/Supabase.
 * Ne pas importer automatiquement ces donnees fictives dans la base reelle.
 * TODO_AUTH : deduire utilisateur_id de la session ; verifier la propriete de
 * chaque ticket et le role support cote serveur, y compris pour les lectures.
 * TODO_SECURITY : valider les entrees, proteger les mutations contre CSRF et
 * utiliser des requetes preparees. Aucun secret Supabase ne doit etre expose ici.
 * KEEP_DOMAIN : urgence signalee et priorite support sont independantes ; priorite
 * initiale NULL, zone facultative, assigne_a_id nullable, aucun cree_par_id.
 * TODO_HISTORY : modifier le ticket et inserer ticket_historique dans une meme
 * transaction ; renseigner l'acteur depuis la session, updated_at et closed_at.
 * TODO_MESSAGES : les notes internes sont simulees ; les reponses publiques restent
 * a implementer. Filtrer les notes internes cote serveur, jamais uniquement dans le DOM.
 * KEEP_UI : le JS de confort et le rendu via textContent peuvent rester ; les
 * validations et controles de droits du navigateur ne font jamais autorite.
 */
(() => {
    'use strict';
    const storageKey = 'yggdrasil.support.demo.v1';
    const priorities = { '': 'À qualifier', urgente: 'Urgente', haute: 'Haute', normale: 'Normale', basse: 'Basse' };
    const statuses = { ouvert: 'Ouvert', en_cours: 'En cours', resolu: 'Résolu', ferme: 'Fermé' };
    const ranks = { urgente: 1, haute: 2, normale: 3, basse: 4 };
    // Identifiants fictifs : a remplacer par les zones de la base au branchement serveur.
    // TODO_BACKEND : charger les vraies zones et leurs identifiants depuis le serveur.
    const zones = { 'demo-accueil': 'Accueil principal', 'demo-atelier': 'Atelier projet',
        'demo-laboratoire': 'Laboratoire réseau', 'demo-serveurs': 'Salle serveurs' };
    const zoneName = ticket => ticket.zone_id ? (zones[ticket.zone_id] ?? 'Zone indisponible') : 'Aucune zone concernée';
    // TEMP_DEMO : catalogue fictif, pas de noms ou emails supposes dans le schema SQL.
    // TODO_BACKEND : joindre utilisateur via utilisateur_id ; adapter les libelles au schema reel.
    const demoUsers = { 'demo-local': 'Utilisateur de démonstration',
        'demo-camille': 'Camille Martin · EPSI-2048', 'demo-noah': 'Noah Bernard · EPSI-1936',
        'demo-ines': 'Inès Petit · EPSI-2214' };
    const requesterId = ticket => ticket.utilisateur_id ?? 'demo-local';
    const requesterName = ticket => demoUsers[requesterId(ticket)] ?? `Utilisateur ${requesterId(ticket)}`;
    const dateLabel = value => new Date(value).toLocaleString('fr-FR');
    const feedback = document.querySelector('#ticket-feedback, #support-feedback');
    let tickets;
    try {
        tickets = JSON.parse(localStorage.getItem(storageKey) || '[]');
        if (!Array.isArray(tickets) || tickets.some(ticket => !ticket || typeof ticket.id !== 'string' ||
            typeof ticket.titre !== 'string' || typeof ticket.description !== 'string' ||
            typeof ticket.a_prioriser_rapidement !== 'boolean' ||
            !Object.hasOwn(priorities, ticket.priorite ?? '') || !Object.hasOwn(statuses, ticket.statut) ||
            !Array.isArray(ticket.historique))) throw new Error('Invalid data');
    } catch {
        feedback.textContent = 'Le stockage local est indisponible ou contient des données invalides. Les tickets ne peuvent pas être chargés.';
        return;
    }

    // TEMP_DEMO : seule persistance actuelle, partagee par tout le navigateur.
    function save(next) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(next));
            tickets = next;
            return true;
        } catch {
            feedback.textContent = 'Enregistrement local impossible. Vos modifications ne sont pas enregistrées.';
            return false;
        }
    }

    const creationForm = document.querySelector('#ticket-form');
    if (creationForm) {
        const zone = document.querySelector('#ticket-zone');
        Object.entries(zones).forEach(([id, name]) => {
            const option = element('option', name);
            option.value = id;
            zone.append(option);
        });
        const title = document.querySelector('#ticket-titre');
        const description = document.querySelector('#ticket-description');
        creationForm.addEventListener('input', () => {
            title.setCustomValidity('');
            description.setCustomValidity('');
            feedback.textContent = '';
        });
        creationForm.addEventListener('submit', event => {
            event.preventDefault();
            title.setCustomValidity(title.value.trim() ? '' : 'Renseignez un objet.');
            description.setCustomValidity(description.value.trim() ? '' : 'Décrivez votre demande.');
            if (!creationForm.reportValidity()) return;
            const now = new Date().toISOString();
            // TEMP_DEMO : identite fictive ; en production, identite issue de la session.
            const ticket = {
                id: `DEMO-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                utilisateur_id: 'demo-local', assigne_a_id: null,
                zone_id: zone.value || null,
                titre: title.value.trim(), description: description.value.trim(),
                a_prioriser_rapidement: document.querySelector('#ticket-urgence').checked,
                priorite: null, statut: 'ouvert', created_at: now, updated_at: now,
                closed_at: null, historique: []
            };
            if (save([...tickets, ticket])) {
                creationForm.reset();
                feedback.textContent = 'Ticket de démonstration créé. Retrouvez son évolution dans « Mes tickets ».';
            }
        });
        document.querySelector('#ticket-preview-button').disabled = false;
    }

    window.addEventListener('storage', event => {
        if (event.key === storageKey || event.key === null) window.location.reload();
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted) window.location.reload();
    });
    const personalList = document.querySelector('#my-tickets');
    if (personalList) {
        const statusFilter = document.querySelector('#my-tickets-status');
        const displayValue = (field, value) => {
            if (field === 'statut') return statuses[value] ?? value;
            if (field === 'priorite') return priorities[value ?? ''] ?? value;
            if (field === 'a_prioriser_rapidement') return value ? 'Signalée' : 'Non signalée';
            return value ?? 'Non renseigné';
        };
        function renderMine() {
            personalList.replaceChildren();
            // TODO_AUTH : filtrer par utilisateur connecte dans la requete serveur.
            // Le filtre ci-dessous ne fait que filtrer les statuts du demandeur fictif.
            const visible = tickets.filter(ticket => requesterId(ticket) === 'demo-local' && (!statusFilter.value || ticket.statut === statusFilter.value))
                .sort((a, b) => b.updated_at.localeCompare(a.updated_at));
            if (!visible.length) personalList.append(element('p', tickets.length ? 'Aucun ticket avec ce statut.' : 'Vous n’avez pas encore de ticket. Créez une demande pour commencer le suivi.'));
            visible.forEach(ticket => {
                const card = element('article', undefined, 'dashboard-section ticket-preview');
                card.append(element('p', statuses[ticket.statut], 'eyebrow'), element('h2', ticket.titre));
                card.append(element('p', `Créé le ${new Date(ticket.created_at).toLocaleString('fr-FR')} · Mis à jour le ${new Date(ticket.updated_at).toLocaleString('fr-FR')}`, 'ticket-help'));
                const info = element('dl');
                [['Priorité du support', priorities[ticket.priorite ?? '']], ['Zone concernée', zoneName(ticket)],
                    ['Urgence à examiner', ticket.a_prioriser_rapidement ? 'Oui' : 'Non']].forEach(([label, value]) => {
                    info.append(element('dt', label), element('dd', value));
                });
                card.append(info);
                const details = element('details');
                details.append(element('summary', 'Description et évolution'));
                details.append(element('p', ticket.description, 'ticket-description'));
                const history = element('ul');
                history.append(element('li', `${new Date(ticket.created_at).toLocaleString('fr-FR')} · Ticket créé, en attente de qualification.`));
                const labels = { statut: 'Statut', priorite: 'Priorité', a_prioriser_rapidement: 'Urgence' };
                ticket.historique.forEach(entry => history.append(element('li',
                    `${new Date(entry.created_at).toLocaleString('fr-FR')} · ${labels[entry.champ] ?? entry.champ} : ${displayValue(entry.champ, entry.ancienne_valeur)} → ${displayValue(entry.champ, entry.nouvelle_valeur)}`)));
                details.append(history);
                card.append(details);
                personalList.append(card);
            });
        }
        statusFilter.addEventListener('change', renderMine);
        renderMine();
    }

    const list = document.querySelector('#support-tickets');
    if (!list) return;
    const urgentOnly = document.querySelector('#support-urgent-only');
    const userFilter = document.querySelector('#support-user');
    const statusFilter = document.querySelector('#support-status');
    const priorityFilter = document.querySelector('#support-priority');
    const search = document.querySelector('#support-search');
    const from = document.querySelector('#support-from');
    const to = document.querySelector('#support-to');
    const rows = document.querySelector('#support-rows');
    let selectedId = null;
    function fillOptions(select, values) {
        Object.entries(values).forEach(([value, label]) => {
            const option = element('option', label); option.value = value; select.append(option);
        });
    }
    fillOptions(statusFilter, statuses);
    fillOptions(priorityFilter, priorities);
    function refreshUsers() {
        const previous = userFilter.value;
        userFilter.replaceChildren();
        fillOptions(userFilter, { '': 'Tous les demandeurs', ...Object.fromEntries(tickets.map(ticket => [requesterId(ticket), requesterName(ticket)])) });
        userFilter.value = [...tickets].some(ticket => requesterId(ticket) === previous) ? previous : '';
    }
    refreshUsers();
    document.querySelector('#support-filters').addEventListener('submit', event => event.preventDefault());
    document.querySelector('#support-filters').addEventListener('input', render);
    document.querySelector('#support-filters').addEventListener('change', render);
    document.querySelector('#support-filters').addEventListener('reset', () => {
        setTimeout(() => { urgentOnly.checked = false; render(); }, 0);
    });
    document.querySelector('#support-examples').addEventListener('click', () => {
        const examples = [
            ['demo-camille', 'Badge refusé au laboratoire', 'demo-laboratoire', 'ouvert', null, true],
            ['demo-noah', 'Accès à la salle serveurs', 'demo-serveurs', 'en_cours', 'haute', false],
            ['demo-ines', 'Question sur les horaires', null, 'resolu', 'basse', false]
        ].map(([user, titre, zone_id, statut, priorite, urgent], index) => {
            const created_at = new Date(Date.now() - (index + 1) * 86400000).toISOString();
            return { id: `EXEMPLE-${user}`, utilisateur_id: user, assigne_a_id: null, titre,
                description: 'Ticket fictif pour essayer les filtres et les notes du support.', zone_id,
                statut, priorite, a_prioriser_rapidement: urgent, created_at, updated_at: created_at,
                closed_at: null, historique: [], notes: [] };
        }).filter(example => !tickets.some(ticket => ticket.id === example.id));
        if (save([...tickets, ...examples])) { refreshUsers(); render(); feedback.textContent = 'Exemples disponibles. Vos tickets existants sont conservés.'; }
    });
    function element(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    }
    function selectField(form, label, options, value) {
        const wrapper = element('label', label);
        const select = element('select');
        Object.entries(options).forEach(([key, text]) => {
            const option = element('option', text);
            option.value = key;
            select.append(option);
        });
        select.value = value ?? '';
        wrapper.append(select);
        form.append(wrapper);
        return select;
    }
    function render() {
        list.replaceChildren();
        rows.replaceChildren();
        const query = search.value.trim().toLocaleLowerCase('fr');
        const visible = tickets.filter(ticket => {
            const date = new Date(ticket.created_at);
            const day = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            return (!urgentOnly.checked || ticket.a_prioriser_rapidement) &&
                (!userFilter.value || requesterId(ticket) === userFilter.value) &&
                (!statusFilter.value || ticket.statut === statusFilter.value) &&
                (priorityFilter.value === 'all' || (ticket.priorite ?? '') === priorityFilter.value) &&
                (!from.value || day >= from.value) && (!to.value || day <= to.value) &&
                (!query || `${ticket.id} ${ticket.titre} ${requesterName(ticket)}`.toLocaleLowerCase('fr').includes(query));
        })
            .sort((a, b) => Number(b.a_prioriser_rapidement) - Number(a.a_prioriser_rapidement) ||
                (ranks[a.priorite] ?? 0) - (ranks[b.priorite] ?? 0) || a.created_at.localeCompare(b.created_at));
        document.querySelector('#support-count').textContent = `${visible.length} ticket(s) sur ${tickets.length}`;
        if (!visible.length) list.append(element('p', 'Aucun ticket ne correspond aux filtres.'));
        visible.forEach(ticket => {
            const row = element('tr');
            const subject = element('td');
            const open = element('button', ticket.titre, 'support-open');
            open.type = 'button';
            open.addEventListener('click', () => { selectedId = ticket.id; render(); list.tabIndex = -1; list.focus(); });
            subject.append(open, element('small', ticket.id));
            row.append(subject, element('td', requesterName(ticket)),
                element('td', `${statuses[ticket.statut]} · ${priorities[ticket.priorite ?? '']}${ticket.a_prioriser_rapidement ? ' · Urgence signalée' : ''}`),
                element('td', dateLabel(ticket.created_at)), element('td', dateLabel(ticket.updated_at)),
                element('td', String((ticket.notes ?? []).length)));
            rows.append(row);
        });
        if (!visible.some(ticket => ticket.id === selectedId)) selectedId = null;
        if (visible.length && !selectedId) list.append(element('p', 'Sélectionnez un ticket pour ouvrir sa fiche.'));
        visible.filter(ticket => ticket.id === selectedId).forEach(ticket => {
            const card = element('article', undefined, 'dashboard-section ticket-preview');
            card.append(element('p', ticket.a_prioriser_rapidement ? 'Urgence signalée · À examiner rapidement' : 'Aucun signalement urgent', 'eyebrow'));
            card.append(element('h2', ticket.titre));
            card.append(element('p', `${ticket.id} · ${requesterName(ticket)} · Créé le ${dateLabel(ticket.created_at)} · Mis à jour le ${dateLabel(ticket.updated_at)}`, 'ticket-help'));
            card.append(element('p', ticket.description, 'ticket-description'));
            card.append(element('p', `Zone concernée : ${zoneName(ticket)}`, 'ticket-help'));
            const form = element('form', undefined, 'ticket-form');
            const priority = selectField(form, 'Priorité définie par le support', priorities, ticket.priorite);
            const status = selectField(form, 'Statut du traitement', statuses, ticket.statut);
            const urgentLabel = element('label', undefined, 'ticket-checkbox');
            const urgent = element('input');
            urgent.type = 'checkbox';
            urgent.checked = ticket.a_prioriser_rapidement;
            urgentLabel.append(urgent, document.createTextNode(' À prioriser rapidement'));
            form.append(urgentLabel, element('p', 'Après examen, décochez le signalement urgent. La priorité reste indépendante.', 'ticket-help'));
            const button = element('button', 'Enregistrer les modifications', 'button');
            button.type = 'submit';
            form.append(button);
            // TODO_AUTH : reserver ces mutations au support cote serveur.
            form.addEventListener('submit', event => {
                event.preventDefault();
                const now = new Date().toISOString();
                const changes = { priorite: priority.value || null, statut: status.value, a_prioriser_rapidement: urgent.checked };
                const history = Object.entries(changes).filter(([key, value]) => ticket[key] !== value)
                    .map(([champ, nouvelle_valeur]) => ({ champ, ancienne_valeur: ticket[champ], nouvelle_valeur, created_at: now }));
                if (!history.length) {
                    feedback.textContent = 'Aucune modification à enregistrer.';
                    return;
                }
                const updated = { ...ticket, ...changes, updated_at: now,
                    closed_at: changes.statut === 'ferme' ? (ticket.closed_at || now) : null,
                    historique: [...ticket.historique, ...history] };
                if (save(tickets.map(item => item.id === ticket.id ? updated : item))) {
                    render();
                    feedback.textContent = `Modifications enregistrées localement pour « ${ticket.titre} ».`;
                    feedback.tabIndex = -1;
                    feedback.focus();
                }
            });
            card.append(form);
            const noteSection = element('section');
            noteSection.append(element('h3', 'Notes internes du support'));
            noteSection.append(element('p', 'Non affichées dans « Mes tickets ». Démonstration : ces notes restent accessibles dans le stockage du navigateur.', 'ticket-help'));
            const noteList = element('ul');
            (ticket.notes ?? []).forEach(note => {
                const item = element('li');
                item.append(element('p', `${note.auteur} · ${dateLabel(note.created_at)}`, 'ticket-help'), element('p', note.contenu, 'ticket-description'));
                noteList.append(item);
            });
            noteSection.append(noteList);
            const noteForm = element('form', undefined, 'ticket-form');
            const label = element('label', 'Ajouter une note interne');
            const noteInput = element('textarea');
            noteInput.required = true; noteInput.maxLength = 5000; noteInput.rows = 3;
            label.append(noteInput);
            const addNote = element('button', 'Enregistrer la note', 'button'); addNote.type = 'submit';
            noteForm.append(label, addNote);
            noteInput.addEventListener('input', () => noteInput.setCustomValidity(''));
            // TODO_BACKEND : ticket_message.interne = TRUE ; auteur issu de la session support.
            // Ne jamais retourner ces messages dans une reponse destinee au demandeur.
            noteForm.addEventListener('submit', event => {
                event.preventDefault();
                noteInput.setCustomValidity(noteInput.value.trim() ? '' : 'Renseignez une note.');
                if (!noteForm.reportValidity()) return;
                const now = new Date().toISOString();
                const updated = { ...ticket, updated_at: now, notes: [...(ticket.notes ?? []),
                    { contenu: noteInput.value.trim(), auteur: 'Agent support (démo)', created_at: now }] };
                if (save(tickets.map(item => item.id === ticket.id ? updated : item))) {
                    render(); feedback.textContent = 'Note interne enregistrée localement.';
                }
            });
            noteSection.append(noteForm); card.append(noteSection);
            if (ticket.historique.length) {
                const details = element('details');
                details.append(element('summary', 'Historique des modifications'));
                const entries = element('ul');
                ticket.historique.forEach(entry => entries.append(element('li',
                    `${new Date(entry.created_at).toLocaleString('fr-FR')} · ${entry.champ} : ${entry.ancienne_valeur ?? 'À qualifier'} → ${entry.nouvelle_valeur ?? 'À qualifier'}`)));
                details.append(entries);
                card.append(details);
            }
            list.append(card);
        });
    }
    urgentOnly.addEventListener('change', render);
    render();
})();
