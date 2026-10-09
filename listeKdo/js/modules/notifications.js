import { $, $$, html, post, csrfToken } from './core.js';
import { toast } from './ui.js';

export function initNotifications() {
    /* ---------- Notifications ---------- */

    const drawer = $('[data-notifications]');

    function setUnreadCount(count) {
        const bell = $('.bell');
        let badge = $('[data-notification-badge]');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge';
                badge.dataset.notificationBadge = '';
                bell?.append(badge);
            }
            badge.textContent = count > 9 ? '9+' : count;
        } else {
            badge?.remove();
        }
        $$('[data-unread-count]').forEach((element) => { element.textContent = count; });
        const markAll = $('[data-mark-all-read]');
        if (markAll) markAll.hidden = count === 0;
    }

    function setRead(item, read) {
        item.classList.toggle('notification--new', !read);
        const toggle = item.querySelector('[data-notification-toggle]');
        if (toggle) {
            const label = read ? 'Marquer comme non lue' : 'Marquer comme lue';
            toggle.setAttribute('aria-label', label);
            toggle.dataset.tip = label;
        }
    }

    if (drawer) {
        const states = drawer.dataset.states === '1';
        const list = $('[data-notification-list]', drawer);
        let filter = 'all';
        let seen = false;

        const toggleNotifications = (force) => {
            const open = force ?? !drawer.classList.contains('is-open');
            drawer.hidden = false;
            requestAnimationFrame(() => drawer.classList.toggle('is-open', open));
            $$('[data-toggle-notifications][aria-controls]').forEach((button) => button.setAttribute('aria-expanded', open));

            // Sans suivi lu / non lu (migration non faite) : ouvrir le panneau marque tout comme lu.
            if (open && !states && !seen && $('[data-notification-badge]')) {
                seen = true;
                post('actions/updateSeenNotif.php', new FormData()).then(() => setUnreadCount(0)).catch(() => { seen = false; });
            }
        };

        const loadNotifications = async (offset) => {
            const response = await fetch(`actions/notifications.php?filter=${filter}&offset=${offset}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!data.ok) throw new Error();
            return data;
        };

        const moreButton = (offset) => {
            const item = document.createElement('li');
            item.className = 'notifications__more';
            item.innerHTML = `<button type="button" class="btn btn--soft" data-more-notifications data-offset="${offset}">Voir plus</button>`;
            return item;
        };

        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-toggle-notifications]')) {
                toggleNotifications();
            } else if (drawer.classList.contains('is-open') && !drawer.contains(event.target)) {
                toggleNotifications(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) toggleNotifications(false);
        });

        drawer.addEventListener('click', async (event) => {
            const target = event.target;

            // Onglets « Toutes » / « Non lues »
            const tab = target.closest('[data-notification-filter]');
            if (tab) {
                filter = tab.dataset.notificationFilter;
                $$('[data-notification-filter]', drawer).forEach((button) => button.setAttribute('aria-selected', button === tab));
                try {
                    const data = await loadNotifications(0);
                    list.replaceChildren(html(data.html));
                    if (data.hasMore) list.append(moreButton(data.offset));
                    $('[data-notifications-empty]', drawer).hidden = Boolean(data.html.trim());
                    setUnreadCount(data.unread);
                } catch {
                    toast('Impossible de charger les notifications.', 'error');
                }
                return;
            }

            // « Voir plus » : 10 notifications de plus.
            const more = target.closest('[data-more-notifications]');
            if (more && !more.getAttribute('aria-busy')) {
                more.setAttribute('aria-busy', 'true');
                try {
                    const data = await loadNotifications(more.dataset.offset);
                    more.parentElement.before(html(data.html));
                    more.dataset.offset = data.offset;
                    if (!data.hasMore) more.parentElement.remove();
                } catch {
                    toast('Impossible de charger les notifications.', 'error');
                } finally {
                    more.removeAttribute('aria-busy');
                }
                return;
            }

            // Basculer lue / non lue
            const toggle = target.closest('[data-notification-toggle]');
            if (toggle) {
                const item = toggle.closest('[data-notification]');
                const read = item.classList.contains('notification--new');
                const body = new FormData();
                body.append('id', item.dataset.notification);
                body.append('read', read ? '1' : '0');
                try {
                    const data = await post('actions/notificationRead.php', body);
                    setRead(item, read);
                    setUnreadCount(data.unread);
                    if (filter === 'unread' && read) item.remove();
                } catch (error) {
                    toast(error.message, 'error');
                }
                return;
            }

            // « Tout marquer comme lu »
            if (target.closest('[data-mark-all-read]')) {
                try {
                    await post('actions/updateSeenNotif.php', new FormData());
                    $$('[data-notification]', drawer).forEach((item) => setRead(item, true));
                    if (filter === 'unread') list.replaceChildren();
                    setUnreadCount(0);
                } catch (error) {
                    toast(error.message, 'error');
                }
                return;
            }

            // Ouvrir une notification la marque comme lue (envoyé même si la page change).
            const link = target.closest('[data-notification-link]');
            if (link) {
                const item = link.closest('[data-notification]');
                if (states && item.classList.contains('notification--new')) {
                    const body = new FormData();
                    body.append('_token', csrfToken);
                    body.append('id', item.dataset.notification);
                    body.append('read', '1');
                    navigator.sendBeacon('actions/notificationRead.php', body);
                    setRead(item, true);
                    const badge = $('[data-notification-badge]');
                    const count = Number($('[data-unread-count]')?.textContent || 0);
                    if (badge || count) setUnreadCount(Math.max(0, count - 1));
                }
                // Lien vers une idée de la page courante : on ferme le panneau, la fiche s'ouvre.
                if (new URL(link.href).search === location.search) toggleNotifications(false);
            }
        });
    }
}
