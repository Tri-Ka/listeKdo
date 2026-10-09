import { $, $$, csrfToken } from './core.js';
import { openDialog } from './dialogs.js';

export function initIdeaForm() {
    /* ---------- Formulaire d'idée (ajout / modification) ---------- */

    const objectDialog = $('#object-form-dialog');

    if (objectDialog) {
        const form = $('[data-object-form]', objectDialog);
        const deleteForm = $('#delete-object-form');
        const preview = $('[data-image-preview]', form);
        const status = $('[data-metadata-status]', form);
        const fields = form.elements;

        const showPreview = (src) => {
            preview.hidden = !src;
            if (src) preview.src = src;
            $$('[data-image-choice]', form).forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.imageChoice === src)));
        };

        // Plusieurs images trouvées : vignettes cliquables pour choisir celle de l'idée.
        const choices = $('[data-image-choices]', form);
        const showChoices = (images) => {
            const list = $('[data-image-choices-list]', choices);
            list.replaceChildren();
            images.forEach((src) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'image-choices__item';
                button.dataset.imageChoice = src;
                button.setAttribute('aria-label', 'Choisir cette image');
                button.setAttribute('aria-pressed', String(src === fields.image.value));
                const img = document.createElement('img');
                img.src = src;
                img.alt = '';
                img.loading = 'lazy';
                img.referrerPolicy = 'no-referrer';
                // Image introuvable ou interdite d'affichage hors du site : on la retire.
                img.addEventListener('error', () => {
                    button.remove();
                    if (list.children.length < 2) choices.hidden = true;
                });
                button.append(img);
                list.append(button);
            });
            choices.hidden = images.length < 2;
        };

        choices.addEventListener('click', (event) => {
            const button = event.target.closest('[data-image-choice]');
            if (!button) return;
            fields.file.value = '';
            fields.image.value = button.dataset.imageChoice;
            showPreview(button.dataset.imageChoice);
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-open-object-form]');
            if (!trigger) return;

            const object = trigger.dataset.openObjectForm ? JSON.parse(trigger.dataset.openObjectForm) : null;
            form.reset();
            status.textContent = '';
            form.action = object ? form.dataset.editAction : form.dataset.addAction;
            fields.object_id.value = object?.id ?? '';
            if (fields.list_owner) {
                const canMove = !!object && !object.suggestion;
                fields.list_owner.value = fields.owner.value;
                fields.list_owner.disabled = !canMove;
                $('[data-object-list]', form).hidden = !canMove;
            }
            fields.nom.value = object?.nom ?? '';
            fields.description.value = object?.description ?? '';
            fields.image.value = object?.image ?? '';
            fields.link.value = object?.link ?? '';
            if (fields.price) fields.price.value = object?.price ?? '';
            deleteForm.elements.id.value = object?.id ?? '';
            $('[data-delete-object]', form).hidden = !object;
            $('[data-object-form-title]', form).textContent = object ? `Modifier : ${object.nom}` : form.dataset.addTitle;
            $('[data-object-form-submit]', form).textContent = object ? 'Enregistrer' : form.dataset.addLabel;
            showPreview(object?.image);
            showChoices([]);
            setCollection(object?.items || []);
            openDialog(objectDialog);
        });

        // Collection : nom et lien facultatif de chaque élément, ajoutés avec « + ».
        const collection = $('[data-collection]', form);

        function addCollectionRow(item = null, focus = false) {
            const row = $('[data-collection-row]', collection).content.firstElementChild.cloneNode(true);
            row.querySelector('[name="item_ids[]"]').value = item?.id ?? '';
            const input = row.querySelector('[name="items[]"]');
            input.value = item?.nom ?? '';
            const link = row.querySelector('[name="item_links[]"]');
            if (link) link.value = item?.link ?? '';
            $('[data-collection-list]', collection).append(row);
            if (focus) input.focus();
            return input;
        }

        function toggleCollection(enabled) {
            $('[data-collection-editor]', collection).hidden = !enabled;
            $$('[data-collection-list] input', collection).forEach((input) => { input.disabled = !enabled; });
            $$('[name="items[]"]', collection).forEach((input) => { input.required = false; });
            if (enabled && !$('[data-collection-list] li', collection)) addCollectionRow(null, true);
        }

        function setCollection(items) {
            if (!collection) return;
            $('[data-collection-list]', collection).replaceChildren();
            const toggle = $('[data-collection-toggle]', collection);
            toggle.checked = items.length > 0;
            items.forEach((item) => addCollectionRow(item));
            toggleCollection(toggle.checked);
        }

        if (collection) {
            $('[data-collection-toggle]', collection).addEventListener('change', (event) => toggleCollection(event.target.checked));
            $('[data-collection-add]', collection).addEventListener('click', () => addCollectionRow(null, true));
            collection.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-collection-remove]');
                if (!remove) return;
                const row = remove.closest('li');
                const next = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                next?.querySelector('[name="items[]"]')?.focus();
            });
            // Entrée dans un élément : nouvelle ligne au lieu d'envoyer le formulaire.
            collection.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && event.target.matches('[name="items[]"]')) {
                    event.preventDefault();
                    if (event.target.value.trim()) addCollectionRow(null, true);
                }
            });
        }

        fields.image.addEventListener('input', () => {
            if (!fields.file.files.length) showPreview(fields.image.value.trim());
        });

        fields.file.addEventListener('change', () => {
            const [file] = fields.file.files;
            showPreview(file ? URL.createObjectURL(file) : fields.image.value.trim());
        });

        // Remplissage automatique depuis le lien du produit.
        let timer;
        let controller;

        fields.link.addEventListener('input', () => {
            clearTimeout(timer);
            const url = fields.link.value.trim();
            if (url.length < 8) return;

            timer = setTimeout(async () => {
                controller?.abort();
                controller = new AbortController();
                status.textContent = 'Recherche des informations du produit…';
                status.classList.add('is-loading');

                try {
                    const body = new FormData();
                    body.append('pageUrl', url);
                    const response = await fetch('actions/fetch_metadata.php', {
                        method: 'POST',
                        body,
                        signal: controller.signal,
                        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
                    });
                    let metadata = await response.json();

                    // Le serveur (Free) est souvent bloqué : on demande alors à Microlink, directement depuis le navigateur.
                    if (!metadata.success || metadata.partial || !metadata.image) {
                        const extra = await microlinkMetadata(url, controller.signal);
                        if (extra) {
                            metadata = {
                                success: true,
                                // Un nom deviné depuis le lien (« partial ») vaut moins que le vrai titre de la page.
                                title: metadata.success && !metadata.partial && metadata.title ? metadata.title : (extra.title || metadata.title),
                                partial: !extra.title && (metadata.partial || !metadata.success),
                                description: metadata.description || extra.description,
                                image: metadata.image || extra.image,
                                images: [...(metadata.images || []), ...(extra.image ? [extra.image] : [])],
                                price: metadata.price || '',
                            };
                        }
                    }

                    // On n'écrase jamais ce que l'utilisateur a déjà saisi.
                    if (metadata.success) {
                        if (metadata.title && !fields.nom.value) fields.nom.value = productTitle(metadata.title);
                        if (metadata.description && !fields.description.value) fields.description.value = metadata.description;
                        if (metadata.image && !fields.image.value && !fields.file.files.length) {
                            fields.image.value = metadata.image;
                            showPreview(metadata.image);
                        }
                        if (metadata.price && fields.price && !fields.price.value) fields.price.value = String(metadata.price).replace('.', ',');
                        if (!fields.file.files.length) showChoices([...new Set(metadata.images || [])]);
                    }
                    status.textContent = metadata.partial || !metadata.success
                        ? 'Ce site bloque la lecture automatique : complétez à la main (ou utilisez l’extension Chrome).'
                        : (metadata.image ? 'Informations récupérées ✔' : 'Informations récupérées ✔ (pas d’image trouvée)');
                } catch (error) {
                    if (error.name !== 'AbortError') status.textContent = '';
                } finally {
                    status.classList.remove('is-loading');
                }
            }, 600);
        });
    }

    /* « L’orchidée 10311 | Creator Expert | Boutique LEGO® » -> « L’orchidée 10311 » (nom du site retiré). */
    function productTitle(title) {
        const [first] = title.split(' | ');
        return first.trim().length >= 4 ? first.trim() : title;
    }

    /*
     * Microlink (offre gratuite, sans clé, ~25 liens par jour et par visiteur) lit la page à notre place.
     * Appelé depuis le navigateur : Free ne peut pas le joindre, et aucun secret n'est nécessaire.
     */
    async function microlinkMetadata(url, signal) {
        try {
            const response = await fetch(`https://api.microlink.io/?url=${encodeURIComponent(url)}`, { signal });
            const result = await response.json();
            const data = result.status === 'success' ? result.data : null;
            if (!data) return null;
            // Microlink renvoie parfois le lien lui-même comme titre, ou un logo (SVG) comme image : on les ignore.
            const title = data.title && data.title.includes(' ') && !/introuvable|not found|access denied|captcha|robot/i.test(data.title) ? data.title : '';
            const image = data.image?.url && !/\.svg(\?|$)/i.test(data.image.url) ? data.image.url : '';
            if (!title && !image) return null;
            return { title, description: title ? data.description || '' : '', image };
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            return null;
        }
    }
}
