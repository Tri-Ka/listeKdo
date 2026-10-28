import { $ } from './core.js';
import { toast } from './ui.js';
import { setImage } from './comments.js';

/*
 * Recherche de GIF (GIPHY) dans les commentaires, comme dans WhatsApp : bouton « GIF » du champ,
 * tendances puis résultats de recherche, défilement infini. Le GIF choisi est téléchargé par le navigateur
 * et joint au commentaire comme une image envoyée (gardé sur le site, même si GIPHY le retire).
 * La recherche part du navigateur : Free filtre les connexions sortantes du serveur.
 * Clé : Administration › Réglages (giphy_key(), attribut data-giphy-key du formulaire).
 */

const API = 'https://api.giphy.com/v1/gifs/';
const PAGE = 24;
const MAX_BYTES = 2 * 1024 * 1024;

let pop = null;
let form = null;
let query = '';
let offset = 0;
let total = Infinity;
let loading = false;
let request = 0;
let searchTimer = 0;
let lastClosed = 0;

function spriteIcon(name) {
    const sprite = $('svg.icon use')?.getAttribute('href').split('#')[0] || 'img/icons.svg';
    return `<svg class="icon" aria-hidden="true"><use href="${sprite}#i-${name}"></use></svg>`;
}

function createPop() {
    pop = document.createElement('div');
    pop.className = 'gif-pop';
    pop.setAttribute('popover', 'auto');
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', 'Chercher un GIF');
    pop.innerHTML = `
        <label class="emoji-search">
            ${spriteIcon('magnifying-glass')}
            <input type="search" placeholder="Rechercher sur GIPHY" aria-label="Rechercher un GIF" autocomplete="off" enterkeyhint="search" data-gif-search>
        </label>
        <div class="gif-pop__scroll" data-gif-scroll>
            <div class="gif-pop__grid" data-gif-grid></div>
            <p class="gif-pop__status" data-gif-status role="status"></p>
        </div>
        <p class="gif-pop__credit">Propulsé par <strong>GIPHY</strong></p>`;

    pop.addEventListener('toggle', (event) => {
        if (event.newState === 'closed') {
            lastClosed = Date.now();
            form?.querySelector('[data-gif-open]')?.setAttribute('aria-expanded', 'false');
        }
    });

    pop.addEventListener('input', (event) => {
        if (!event.target.matches('[data-gif-search]')) return;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => restart(event.target.value.trim()), 350);
    });

    pop.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.matches('[data-gif-search]')) {
            event.preventDefault();
            clearTimeout(searchTimer);
            restart(event.target.value.trim());
        }
    });

    pop.addEventListener('click', (event) => {
        const choice = event.target.closest('[data-gif-url]');
        if (choice) choose(choice);
    });

    $('[data-gif-scroll]', pop).addEventListener('scroll', (event) => {
        const scroll = event.currentTarget;
        if (scroll.scrollTop + scroll.clientHeight > scroll.scrollHeight - 200) load();
    }, { passive: true });
}

function open(button) {
    // Un clic sur le bouton qui vient de fermer le panneau (clic à l'extérieur) ne le rouvre pas.
    if (Date.now() - lastClosed < 300) return;
    if (!pop) createPop();
    form = button.closest('form');
    (button.closest('dialog') || document.body).append(pop);
    button.setAttribute('aria-expanded', 'true');
    pop.showPopover();
    place();

    const field = $('[data-gif-search]', pop);
    if (!$('[data-gif-grid]', pop).childElementCount || field.value) {
        field.value = '';
        restart('');
    }
    if (window.matchMedia('(hover: hover)').matches) field.focus({ preventScroll: true });
}

/** Au-dessus du formulaire, de sa largeur, sans sortir de l'écran. */
function place() {
    if (!form || !pop.matches(':popover-open')) return;
    const box = form.getBoundingClientRect();
    const viewW = document.documentElement.clientWidth;
    const width = Math.min(Math.max(box.width, 280), 440, viewW - 16);
    const height = pop.offsetHeight;
    const left = Math.min(Math.max(8, box.right - width), viewW - width - 8);
    let top = box.top - height - 8;
    if (top < 8) top = Math.min(box.bottom + 8, window.innerHeight - height - 8);
    pop.style.width = `${width}px`;
    pop.style.left = `${left}px`;
    pop.style.top = `${Math.max(8, top)}px`;
}

function restart(text) {
    query = text;
    offset = 0;
    total = Infinity;
    loading = false;
    request++;
    $('[data-gif-grid]', pop).replaceChildren();
    $('[data-gif-scroll]', pop).scrollTop = 0;
    load();
}

async function load() {
    if (loading || offset >= total) return;
    loading = true;
    const id = request;
    const status = $('[data-gif-status]', pop);
    status.textContent = 'Chargement…';

    const params = new URLSearchParams({ api_key: form.dataset.giphyKey, limit: PAGE, offset, rating: 'pg', lang: 'fr', bundle: 'messaging_non_clips' });
    if (query) params.set('q', query);

    try {
        const response = await fetch(`${API}${query ? 'search' : 'trending'}?${params}`);
        if (id !== request) return;
        if (!response.ok) {
            throw new Error(response.status === 429
                ? 'Trop de recherches de GIF pour le moment, réessayez dans un moment.'
                : 'La recherche de GIF n’est pas disponible.');
        }
        const data = await response.json();
        if (id !== request) return;

        const grid = $('[data-gif-grid]', pop);
        data.data.forEach((gif) => {
            const preview = gif.images?.fixed_width_small || gif.images?.fixed_width;
            const full = gif.images?.fixed_width;
            if (!preview || !full) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'gif-pop__item';
            button.dataset.gifUrl = full.url;
            button.dataset.gifSize = full.size || 0;
            button.dataset.gifSmall = gif.images.fixed_width_small?.url || full.url;
            button.setAttribute('aria-label', gif.title || 'GIF');
            const img = document.createElement('img');
            img.src = preview.webp || preview.url;
            img.alt = '';
            img.loading = 'lazy';
            button.append(img);
            grid.append(button);
        });

        offset += data.data.length;
        total = Math.min(data.pagination?.total_count ?? offset, 499);
        if (!data.data.length) total = offset;
        status.textContent = grid.childElementCount ? '' : 'Aucun GIF trouvé.';
    } catch (error) {
        if (id === request) status.textContent = error.message || 'GIPHY ne répond pas.';
    } finally {
        if (id === request) loading = false;
    }
}

/** Télécharge le GIF choisi et le joint au commentaire (version réduite s'il dépasse 2 Mo). */
async function choose(button) {
    const target = form;
    button.setAttribute('aria-busy', 'true');
    try {
        const url = Number(button.dataset.gifSize) > MAX_BYTES - 50000 ? button.dataset.gifSmall : button.dataset.gifUrl;
        const response = await fetch(url);
        if (!response.ok) throw new Error();
        const blob = await response.blob();
        if (blob.size > MAX_BYTES) throw new Error('Ce GIF est trop lourd, choisissez-en un autre.');
        pop.hidePopover();
        setImage(target, new File([blob], 'giphy.gif', { type: 'image/gif' }));
        $('[data-comment-editor], textarea[name="content"]:not([hidden])', target)?.focus();
    } catch (error) {
        toast(error.message || 'Ce GIF n’a pas pu être récupéré, réessayez.', 'error');
    } finally {
        button.removeAttribute('aria-busy');
    }
}

export function initGifPicker() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-gif-open]');
        if (!button) return;
        if (pop?.matches(':popover-open')) pop.hidePopover();
        else open(button);
    });
    window.addEventListener('resize', place);
    document.addEventListener('scroll', (event) => {
        if (pop && !pop.contains(event.target)) place();
    }, { capture: true, passive: true });
}
