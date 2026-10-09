import { $, $$, storage } from './core.js';
import { grid, layoutGrid } from './grid.js';

/* ---------- Idées offertes et filtres ---------- */

const tabs = $('[data-tabs]');
const priceTools = $('[data-price-tools]');
const cards = () => $$('.card[data-object]');
const active = (card) => card.dataset.received !== '1';
const filters = {
    all: active,
    available: (card) => active(card) && card.dataset.gifted === '0',
    gifted: (card) => active(card) && card.dataset.gifted === '1',
    favorite: (card) => active(card) && card.dataset.favorite === '1',
    suggestion: (card) => active(card) && card.dataset.suggestion === '1',
    received: (card) => !active(card),
};
let currentFilter = 'all';
let budget = 0;
let sort = '';

function sortCards() {
    if (!grid) return;
    const price = (card) => (card.dataset.price === '' ? null : Number(card.dataset.price));
    const sorted = cards().sort((a, b) => {
        if (sort) {
            const pa = price(a);
            const pb = price(b);
            // Les idées sans prix vont à la fin.
            if (pa === null || pb === null) return (pa === null) - (pb === null);
            if (pa !== pb) return sort === 'asc' ? pa - pb : pb - pa;
        }
        return a.dataset.index - b.dataset.index;
    });
    grid.append(...sorted);
}

function refreshFilters() {
    if (!tabs) return;

    const showGifted = document.body.classList.contains('show-gifted');
    if (!showGifted && (currentFilter === 'available' || currentFilter === 'gifted')) currentFilter = 'all';

    for (const [name, test] of Object.entries(filters)) {
        const count = $(`[data-count="${name}"]`, tabs);
        if (count) count.textContent = `(${cards().filter(test).length})`;
    }
    const receivedTab = $('[data-filter="received"]', tabs);
    if (receivedTab) receivedTab.hidden = !cards().some(filters.received) && currentFilter !== 'received';
    const suggestionTab = $('[data-filter="suggestion"]', tabs);
    if (suggestionTab) suggestionTab.hidden = !cards().some(filters.suggestion) && currentFilter !== 'suggestion';

    $$('[data-filter]', tabs).forEach((tab) => tab.setAttribute('aria-pressed', tab.dataset.filter === currentFilter));

    // Menu de sélection (mobile) : mêmes filtres, mêmes compteurs.
    const select = $('[data-tabs-select]');
    if (select) {
        const labels = { all: 'Toutes les idées', available: 'À offrir', gifted: 'Déjà offertes', favorite: 'Coups de cœur', suggestion: 'Suggestions des amis', received: 'Reçus' };
        for (const option of select.options) {
            option.textContent = `${labels[option.value]} (${cards().filter(filters[option.value]).length})`;
            option.hidden = (['available', 'gifted'].includes(option.value) && !showGifted)
                || (['received', 'suggestion'].includes(option.value) && !cards().some(filters[option.value]));
        }
        select.value = currentFilter;
    }

    if (priceTools) {
        priceTools.hidden = !cards().some((card) => active(card) && card.dataset.price !== '');
        $$('[data-budget]', priceTools).forEach((button) => button.setAttribute('aria-pressed', Number(button.dataset.budget || 0) === budget));
        $$('[data-sort]', priceTools).forEach((button) => button.setAttribute('aria-pressed', button.dataset.sort === sort));
    }

    let visible = 0;
    cards().forEach((card) => {
        const price = card.dataset.price === '' ? null : Number(card.dataset.price);
        const show = filters[currentFilter](card) && (!budget || (price !== null && price <= budget));
        card.hidden = !show;
        if (show) visible++;
    });

    const addCard = $('.card--add');
    if (addCard) addCard.hidden = currentFilter !== 'all' || Boolean(budget);
    layoutGrid();

    const empty = $('[data-empty]');
    if (empty) empty.hidden = visible > 0 || Boolean(addCard && !addCard.hidden);
}

export { currentFilter, refreshFilters };

export function initFilters() {
    // Ordre d'origine, pour revenir au tri « Récentes ».
    cards().forEach((card, index) => { card.dataset.index = index; });

    $('[data-tabs-select]')?.addEventListener('change', (event) => {
        currentFilter = event.target.value;
        refreshFilters();
    });

    tabs?.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-filter]');
        if (!tab) return;
        currentFilter = tab.dataset.filter;
        refreshFilters();
    });

    priceTools?.addEventListener('click', (event) => {
        const budgetButton = event.target.closest('[data-budget]');
        const sortButton = event.target.closest('[data-sort]');
        if (budgetButton) budget = Number(budgetButton.dataset.budget || 0);
        if (sortButton) {
            sort = sortButton.dataset.sort;
            sortCards();
        }
        refreshFilters();
    });

    // Deux interrupteurs possibles (barre du haut, et près des onglets sur mobile) : synchronisés.
    const giftSwitches = $$('[data-show-gifted]');

    if (giftSwitches.length) {
        const apply = (checked) => {
            giftSwitches.forEach((input) => { input.checked = checked; });
            document.body.classList.toggle('show-gifted', checked);
            storage('kdo-show-gifted', checked ? '1' : '0');
            refreshFilters();
        };
        giftSwitches.forEach((input) => input.addEventListener('change', () => apply(input.checked)));
        apply(storage('kdo-show-gifted') !== '0');
    } else {
        refreshFilters();
    }
}
