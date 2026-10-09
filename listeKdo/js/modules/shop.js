import { $, $$ } from './core.js';

export function initShop() {
    /* ---------- Boutique : un onglet par type de liste ---------- */

    // Paramètres de la liste : seuls les habillages du type de liste coché sont proposés (et envoyés).
    function syncSkinField(form) {
        const field = form && $('[data-skin-field]', form);
        const theme = form && $('input[name="theme"]:checked', form)?.value;
        if (!field || !theme) return;
        let checked = false;
        $$('[data-skin-theme]', field).forEach((option) => {
            const visible = option.dataset.skinTheme === theme;
            const input = $('input', option);
            option.hidden = !visible;
            input.disabled = !visible;
            if (!visible) input.checked = false;
            if (visible && input.checked) checked = true;
        });
        if (!checked) $(`[data-skin-theme="${theme}"].skin-field__option--classic input`, field).checked = true;
    }
    document.addEventListener('change', (event) => {
        if (event.target.matches('input[name="theme"]')) syncSkinField(event.target.form);
    });

    // Boutique : catégorie (habillages, cadres, compte à rebours) ; les types de liste ne concernent que les habillages.
    document.addEventListener('click', (event) => {
        const cat = event.target.closest('[data-shop-cat]');
        if (!cat) return;
        $$('[data-shop-cat]').forEach((other) => other.setAttribute('aria-pressed', String(other === cat)));
        $$('[data-shop-panel]').forEach((panel) => { panel.hidden = panel.dataset.shopPanel !== cat.dataset.shopCat; });
        const subnav = $('[data-shop-subnav]');
        if (subnav) subnav.hidden = cat.dataset.shopCat !== 'skins';
    });

    // « Plus de cadres » / « Plus d'effets » (paramètres, profil) : la boutique s'ouvre sur la bonne catégorie.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-shop-open]');
        const cat = link && $(`[data-shop-cat="${link.dataset.shopOpen}"]`);
        if (cat) cat.click();
    });

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-shop-tab]');
        if (!tab) return;
        $$('[data-shop-tab]').forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
        $$('[data-shop-section]').forEach((section) => { section.hidden = section.dataset.shopSection !== tab.dataset.shopTab; });
    });

    // Aperçu d'un habillage : fausse liste à ses couleurs, ouverte par-dessus la boutique.
    const skinPreview = $('#skin-preview-dialog');
    let previewSource = null;
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-skin-preview]');
        if (!trigger || !skinPreview) return;
        const data = JSON.parse(trigger.dataset.skinPreview);
        const stage = $('[data-preview-stage]', skinPreview);
        for (const [name, value] of Object.entries(data.colors)) stage.style.setProperty(`--${name}`, value);
        $('[data-preview-title]', skinPreview).src = data.title;
        $('[data-preview-subtitle]', skinPreview).textContent = data.subtitle;
        for (const side of ['left', 'right']) {
            $(`[data-preview-${side}]`, skinPreview).replaceChildren(...data[side].map((src) => {
                const img = document.createElement('img');
                img.src = src;
                img.alt = '';
                return img;
            }));
        }
        $('[data-preview-name]', skinPreview).textContent = data.name;
        $('[data-preview-meta]', skinPreview).textContent = `${data.rarity} · listes « ${data.type} »`;

        // Bouton d'achat (ou « Utiliser ») de la carte, repris tel quel.
        previewSource = trigger.closest('.shop-item').querySelector('.shop-item__action button[type=submit]');
        const buy = $('[data-preview-buy]', skinPreview);
        buy.hidden = !previewSource;
        if (previewSource) buy.innerHTML = previewSource.innerHTML;
        // Pas de bouton (trop cher, déjà porté…) : on recopie l'état affiché sur la carte.
        const price = $('[data-preview-price]', skinPreview);
        const action = trigger.closest('.shop-item').querySelector('.shop-item__action');
        price.hidden = Boolean(previewSource);
        price.innerHTML = previewSource ? '' : [...action.children].filter((el) => el.tagName !== 'INPUT').map((el) => el.outerHTML).join(' ');

        skinPreview.showModal();
    });
    $('[data-preview-buy]', skinPreview || document)?.addEventListener('click', () => {
        skinPreview.close();
        previewSource?.click();
    });
}
