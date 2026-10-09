const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const csrfToken = $('meta[name="csrf-token"]')?.content ?? '';

/* ---------- Appels serveur ---------- */

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin',
    });

    let data = null;
    try {
        data = await response.json();
    } catch {
        // Réponse non JSON (erreur PHP, coupure réseau…).
    }

    if (!response.ok || !data?.ok) {
        throw new Error(data?.message || 'Une erreur est survenue, réessayez.');
    }

    return data;
}

function html(markup) {
    const template = document.createElement('template');
    template.innerHTML = markup.trim();
    return template.content;
}

function storage(key, value) {
    try {
        if (value === undefined) return localStorage.getItem(key);
        localStorage.setItem(key, value);
    } catch {
        return null;
    }
}

export { $, $$, csrfToken, post, html, storage };
