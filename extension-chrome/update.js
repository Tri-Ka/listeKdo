/*
 * Mise à jour de l'extension : le site publie la dernière version dans
 * download/extension-version.txt (généré par docker/build-extension.sh, à côté du zip).
 * Chargé par la fenêtre (popup.html) et par le service worker (background.js).
 */

const DEFAULT_SITE = 'http://datcharrye.free.fr/listeKdo/';

async function currentSite() {
    const stored = await chrome.storage.local.get('site');
    return stored.site || DEFAULT_SITE;
}

// -1, 0 ou 1, en comparant les nombres un par un (« 1.10.0 » > « 1.9.0 »).
function compareVersions(a, b) {
    const left = String(a).split('.').map(Number);
    const right = String(b).split('.').map(Number);
    for (let i = 0; i < Math.max(left.length, right.length); i++) {
        const diff = (left[i] || 0) - (right[i] || 0);
        if (diff) return diff > 0 ? 1 : -1;
    }
    return 0;
}

// Version disponible sur le site si elle est plus récente que celle installée, sinon null.
async function checkUpdate(site) {
    try {
        const response = await fetch(`${site}download/extension-version.txt?t=${Date.now()}`, { cache: 'no-store' });
        if (!response.ok) return null;
        const latest = (await response.text()).trim();
        if (!/^\d+(\.\d+)*$/.test(latest)) return null;
        return compareVersions(latest, chrome.runtime.getManifest().version) > 0 ? latest : null;
    } catch {
        return null;
    }
}

// Pastille sur l'icône de l'extension tant qu'une mise à jour attend.
async function showUpdateBadge(latest) {
    await chrome.action.setBadgeText({ text: latest ? '↑' : '' });
    if (latest) {
        await chrome.action.setBadgeBackgroundColor({ color: '#ff6f61' });
        await chrome.action.setTitle({ title: `Liste de Kdo : version ${latest} disponible` });
    } else {
        await chrome.action.setTitle({ title: 'Ajouter à ma liste de Kdo' });
    }
}
