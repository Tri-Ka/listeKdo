import { $, $$, html, post } from './core.js';
import { toast } from './ui.js';
import { openDialog } from './dialogs.js';
import { currentFilter, refreshFilters } from './filters.js';
import { gridObserver, layoutGrid } from './grid.js';
import { fileInArchive, throwInTrash } from './animations.js';
import { refreshGems } from './gems.js';
import { commentFormData, commentHasContent, resetCommentForm } from './comments.js';

export function initIdeas() {
    /* ---------- Formulaires envoyés en arrière-plan ---------- */

    const ajaxHandlers = {
        gift(form, data) {
            const id = form.elements.id.value;
            $$(`[data-gift-slot="${id}"]`).forEach((slot) => slot.replaceChildren(html(data.html)));
            const card = document.getElementById(`idea-${id}`);
            if (card) {
                card.classList.toggle('card--gifted', data.gifted);
                card.dataset.gifted = data.gifted ? '1' : '0';
            }
            refreshFilters();
            form.closest('#gift-choice-dialog')?.close();
            toast(data.gifted ? 'Noté ! Ce Kdo est réservé pour vous.' : 'Ce Kdo est de nouveau disponible.', 'success');
        },

        favorite(form, data) {
            form.querySelector('.heart-btn')?.setAttribute('aria-pressed', data.favorite);
            const card = form.closest('.card');
            if (card) card.dataset.favorite = data.favorite ? '1' : '0';
            refreshFilters();
        },

        async received(form, data) {
            const card = form.closest('.card') || (data.id ? document.getElementById(`idea-${data.id}`) : null);
            form.closest('details')?.removeAttribute('open');
            // Rangée dans le carton d'archives quand elle quitte l'onglet affiché.
            if (card && data.received && currentFilter !== 'received' && !card.hidden) await fileInArchive(card);
            // Vignette et fiche refaites par le serveur : « Je l'offre », étiquettes et menu suivent l'état.
            if (card && data.card) {
                const fresh = html(data.card).firstElementChild;
                // Pas d'animation d'apparition : la vignette change sur place.
                fresh.style.animation = 'none';
                card.replaceWith(fresh);
                gridObserver?.observe(fresh);
            }
            const dialog = data.dialog && card ? document.getElementById(`object-${card.dataset.object}`) : null;
            if (dialog) {
                if (dialog.open) dialog.replaceChildren(...html(data.dialog).firstElementChild.childNodes);
                else dialog.replaceWith(html(data.dialog));
            }
            toast(data.message, 'success');
            refreshFilters();
        },

        participation(form, data) {
            $$(`[data-gift-slot="${data.id}"]`).forEach((slot) => slot.replaceChildren(html(data.gift)));
            $(`[data-group-slot="${data.id}"]`)?.replaceWith(html(data.group));
            const card = document.getElementById(`idea-${data.id}`);
            if (card) {
                card.classList.toggle('card--gifted', data.complete);
                card.dataset.gifted = data.complete ? '1' : '0';
            }
            toast(data.message, 'success');
            refreshFilters();
        },

        item(form, data) {
            $$(`[data-items-slot="${data.id}"]`).forEach((slot) => {
                slot.replaceWith(html(slot.dataset.itemsMode === 'card' ? data.card : data.detail));
            });
            $$(`[data-gift-slot="${data.id}"]`).forEach((slot) => slot.replaceChildren(html(data.gift)));
            const card = document.getElementById(`idea-${data.id}`);
            if (card) {
                card.classList.toggle('card--gifted', data.complete);
                card.dataset.gifted = data.complete ? '1' : '0';
            }
            refreshFilters();
        },

        reaction(form, data) {
            const id = form.elements.object.value;
            // Vignette et fiche de l'idée.
            $$(`[data-reactions-slot="${id}"]`).forEach((slot) => {
                slot.replaceChildren(html('reactionsCompact' in slot.dataset ? data.compact : data.summary));
            });
            $(`[data-reactors-slot="${id}"]`)?.replaceChildren(html(data.detail));
        },

        comment(form, data) {
            const id = form.elements.productId.value;
            $(`[data-comments="${id}"]`)?.append(html(data.html));
            resetCommentForm(form);
            updateCommentCount(id, data.count);
        },

        'delete-comment'(form, data) {
            const list = form.closest('[data-comments]');
            form.closest('.comment')?.remove();
            if (list) updateCommentCount(list.dataset.comments, data.count);
        },
    };

    function updateCommentCount(id, count) {
        $$(`[data-comment-count="${id}"]`).forEach((badge) => {
            badge.textContent = count;
            badge.hidden = count === 0;
        });
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        const handler = ajaxHandlers[form.dataset.ajax];
        if (!handler) return;

        event.preventDefault();
        if (form.dataset.busy) return;
        if (form.dataset.ajax === 'comment' && !commentHasContent(form)) {
            form.elements.content.focus();
            return;
        }

        const button = event.submitter;
        form.dataset.busy = '1';
        button?.setAttribute('aria-busy', 'true');

        try {
            const body = form.dataset.ajax === 'comment' ? await commentFormData(form, button) : new FormData(form, button);
            handler(form, await post(form.getAttribute('action'), body));
            refreshGems();
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            delete form.dataset.busy;
            button?.removeAttribute('aria-busy');
        }
    });

    // Envoi par Entrée dans un commentaire (Maj+Entrée pour aller à la ligne).
    document.addEventListener('keydown', (event) => {
        const field = event.target;
        if (event.key === 'Enter' && !event.shiftKey && field.matches?.('.comment-form textarea, [data-comment-editor]')) {
            event.preventDefault();
            const form = field.closest('form');
            if (commentHasContent(form)) form.requestSubmit();
        }
    });

    $$('form[data-autosubmit]').forEach((form) => {
        form.addEventListener('change', () => form.requestSubmit());
    });

    /* ---------- Réactions ---------- */

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-reaction-toggle]');
        $$('.reactions__picker.is-open').forEach((picker) => {
            if (!toggle || picker !== toggle.parentElement) picker.classList.remove('is-open');
        });
        toggle?.parentElement.classList.toggle('is-open');
    });

    /* ---------- « Je l'offre » : seul ou à plusieurs ---------- */

    const giftChoice = $('#gift-choice-dialog');

    if (giftChoice) {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-gift-choice]');
            if (trigger) {
                $('[data-gift-choice-id]', giftChoice).value = trigger.dataset.giftChoice;
                $('[data-gift-choice-name]', giftChoice).textContent = trigger.dataset.giftName;
                openDialog(giftChoice);
                return;
            }

            if (event.target.closest('[data-gift-group]')) {
                const id = $('[data-gift-choice-id]', giftChoice).value;
                const dialog = document.getElementById(`object-${id}`);
                openDialog(dialog);
                const amount = dialog?.querySelector('[name="amount"]');
                amount?.scrollIntoView({ block: 'center' });
                amount?.focus();
            }
        });
    }

    /*
     * Suppression d'une idée (form[data-delete-idea] : menu « ⋯ » de la vignette ou fenêtre de modification),
     * en arrière-plan : la vignette part dans la corbeille (throwInTrash), puis disparaît de la liste.
     */
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-delete-idea]');
        if (!form) return;
        event.preventDefault();
        const card = document.getElementById(`idea-${form.elements.id.value}`);
        form.closest('dialog')?.close();
        card?.querySelector('details[open]')?.removeAttribute('open');

        const request = post(form.getAttribute('action'), new FormData(form));
        try {
            const [result] = await Promise.allSettled([request, card ? throwInTrash(card) : null]);
            if (result.status === 'rejected') throw result.reason;
            card?.remove();
            const total = $('[data-count-total]');
            if (total) {
                const count = Math.max(0, Number(total.textContent) - 1);
                total.textContent = count;
                if (total.nextSibling) total.nextSibling.textContent = count > 1 ? ' idées' : ' idée';
            }
            refreshFilters();
            layoutGrid();
            toast('Idée supprimée.', 'success');
            refreshGems();
        } catch (error) {
            if (card) card.style.visibility = '';
            toast(error.message, 'error');
        }
    });
}
