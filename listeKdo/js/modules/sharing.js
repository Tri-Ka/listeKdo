import { $, $$ } from './core.js';
import { toast } from './ui.js';
import { resolveShortLink } from './short-links.js';

export function initSharing() {
    /* ---------- Partage ---------- */

    $('[data-copy]')?.addEventListener('click', async () => {
        const field = $('[data-share-url]');
        try {
            await navigator.clipboard.writeText(field.value);
        } catch {
            field.select();
            document.execCommand('copy');
        }
        toast('Lien copié !', 'success');
    });

    // Lien d'invitation d'une liste privée (paramètres de la liste).
    $('[data-copy-invite]')?.addEventListener('click', async () => {
        const field = $('[data-invite-url]');
        try {
            await navigator.clipboard.writeText(field.value);
        } catch {
            field.select();
            document.execCommand('copy');
        }
        toast("Lien d'invitation copié !", 'success');
    });

    const nativeShare = $('[data-native-share]');
    if (nativeShare && navigator.share) {
        nativeShare.hidden = false;
        nativeShare.addEventListener('click', () => {
            navigator.share({ title: document.title, url: $('[data-share-url]').value }).catch(() => {});
        });
    }

    // Liens courts en HTTPS : certaines applis, dont WhatsApp, ouvrent les liens en HTTPS,
    // que Free ne gère pas. Le lien court redirige vers le site en HTTP. Chaque bloc [data-short]
    // (partage de la liste, parrainage, invitation) demande le sien après l'affichage (actions/shortUrl.php),
    // puis remplace le lien long dans ses champs et ses liens (pas dans un bloc [data-short] imbriqué).
    $$('[data-short]').forEach((box) => {
        const params = new URLSearchParams({ type: box.dataset.short, user: box.dataset.shortUser || '' });
        fetch(`actions/shortUrl.php?${params}`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then(async (data) => {
                if (!data.ok) return;
                data.url = await resolveShortLink(data);
                if (data.url === data.long) return;
                const own = (el) => el.closest('[data-short]') === box;
                $$('input', box).filter(own).forEach((field) => {
                    if (field.value === data.long) field.value = data.url;
                });
                $$('a[href]', box).filter(own).forEach((link) => {
                    link.href = link.href.replaceAll(encodeURIComponent(data.long), encodeURIComponent(data.url));
                });
            })
            .catch(() => {});
    });
}
