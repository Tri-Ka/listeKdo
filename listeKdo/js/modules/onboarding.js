import { $, $$, post } from './core.js';

export function initOnboarding() {
    /* ---------- Visite guidée ---------- */
    // Une fenêtre d'accueil, puis un projecteur sur chaque fonctionnalité (Précédent / Suivant, flèches du clavier).
    // Chaque étape vise le premier élément visible de `targets` (sinon elle est sautée) ; `menu: true` ouvre
    // d'abord le menu du compte (éléments rangés dedans, ou déplacés dedans sur mobile). Sans cible : fenêtre centrée.

    const onboarding = $('#onboarding-tour');

    if (onboarding) {
        const firstName = onboarding.dataset.tourName.split(' ')[0];
        const tourSteps = [
            { icon: 'gift', eyebrow: 'Bienvenue', title: `Bienvenue ${firstName} !`, text: 'Votre liste de cadeaux est prête. En une minute, faisons le tour de tout ce que vous pouvez y faire.', next: "C'est parti", prev: 'Plus tard' },
            { targets: ['.card--add'], icon: 'plus', eyebrow: 'Votre liste', title: 'Ajoutez vos idées', text: "Collez le lien d'un produit, de n'importe quelle boutique : le nom, l'image et le prix se remplissent tout seuls. Une idée peut aussi regrouper plusieurs éléments (une collection)." },
            { targets: ['.grid .card[data-object]'], icon: 'image', eyebrow: 'Vos idées', title: 'Chaque idée a sa vignette', text: 'Vos proches y voient la photo et le prix, réagissent et laissent des commentaires pour se mettre d’accord.' },
            { targets: ['.grid .card .items'], icon: 'list', eyebrow: 'Vos collections', title: 'Complétez vos collections', text: 'Chaque élément peut avoir un lien vers une boutique et rapporte autant de gemmes qu’une idée. Marquez chaque élément comme reçu avec son bouton. Depuis l’extension Chrome, choisissez une collection existante pour y ajouter un produit.' },
            { targets: ['.grid .card[data-object] .heart-btn'], icon: 'heart', eyebrow: 'Vos envies', title: 'Vos coups de cœur', text: 'Touchez le cœur sur les idées qui vous font le plus envie : vos proches les repèrent tout de suite.' },
            { targets: ['.grid .card[data-object] [data-card-menu] > summary'], icon: 'ellipsis', eyebrow: 'Vos idées', title: 'Modifier, marquer comme reçu', text: 'Ce menu sert à modifier une idée, à la supprimer, ou à indiquer que vous l’avez reçue : elle passe alors dans l’onglet « Reçus ».' },
            { targets: ['.grid .card[data-object] [data-card-menu] > summary'], icon: 'layer-group', eyebrow: 'Vos listes', title: 'Changer une idée de liste', text: 'Si vous gérez plusieurs listes, ouvrez « Modifier » puis choisissez une autre liste dans le champ « Liste ». Les commentaires et réservations sont conservés. Une notification prévient les proches concernés pour retrouver l’idée sur sa nouvelle liste.' },
            { targets: ['[data-tabs]', '.tabs-select'], icon: 'list', eyebrow: 'Vos idées', title: 'Filtrer et trier', text: 'Retrouvez vos coups de cœur ou vos cadeaux reçus. Dès qu’un prix est indiqué, on peut aussi filtrer par budget et trier par prix.' },
            { icon: 'eye', eyebrow: 'La surprise', title: 'Vous ne saurez pas qui offre quoi', text: 'Vos proches réservent les idées, seuls ou en se cotisant à plusieurs, sans que vous le voyiez. Eux voient ce qui est déjà pris : pas de doublon, et la surprise reste entière.' },
            { targets: ['.profile__avatar'], icon: 'user', eyebrow: 'Votre profil', title: 'Votre photo et votre profil', text: 'Changez votre photo, votre nom et votre mot de passe ici. Ils sont aussi dans le menu du compte.' },
            { targets: ['[data-countdown]', '.topbar__days'], icon: 'calendar-days', eyebrow: 'Le grand jour', title: 'Le compte à rebours', text: 'La date de votre événement s’affiche ici. Vos amis reçoivent un rappel à l’approche du jour J.' },
            { targets: ['.topbar__settings', { sel: '.user-menu__mobile [data-open="list-settings-dialog"]', menu: true }], icon: 'gear', eyebrow: 'À votre image', title: 'Les paramètres de la liste', text: 'Choisissez le type de liste (anniversaire, Noël, naissance, mariage…), son titre, la date de l’événement, et rendez-la privée si besoin, visible seulement par les proches que vous invitez.' },
            { targets: ['.topbar__share', { sel: '.user-menu__mobile [data-open="share-dialog"]', menu: true }], icon: 'share-nodes', eyebrow: 'À partager', title: 'Envoyez votre liste', text: $('.profile__private') ? 'Votre liste est privée : ce lien invite vos proches à la consulter après connexion ou création d’un compte.' : 'Copiez le lien ou partagez-le sur WhatsApp : vos proches voient votre liste sans avoir besoin de compte.' },
            { targets: ['.friends', '.friends-shortcut', '[data-mobile-friends]'], icon: 'users', eyebrow: 'Vos proches', title: 'Les listes de vos amis', text: 'Retrouvez ici les listes de vos amis, triées par prochain événement. Pour en ajouter un, ouvrez sa liste et touchez « Ajouter à mes amis ».' },
            { targets: ['.friends', '.friends-shortcut', '[data-mobile-friends]'], icon: 'lightbulb', eyebrow: 'Vos proches', title: 'Suggérez-leur des idées', text: 'Sur la liste d’un ami, « Suggérer une idée » ajoute une idée qu’il ne verra jamais : seuls ses autres amis la voient, pour la lui offrir.' },
            { targets: ['.bell'], icon: 'bell', eyebrow: 'Ne rien rater', title: 'Les notifications', text: 'Nouvelles idées de vos amis, commentaires, réactions, rappels d’anniversaire : tout arrive ici.' },
            { targets: ['.profile__badges', { sel: '[data-my-badges]', menu: true }], icon: 'crown', eyebrow: 'Récompenses', title: 'Vos badges', text: 'Ajouter des idées, offrir, commenter… chaque étape débloque des badges. Touchez la pastille ou « Mes badges » dans le menu du compte pour voir les prochains.' },
            { targets: ['.gem-pill'], icon: 'palette', eyebrow: 'Récompenses', title: 'Gemmes et boutique', text: 'Badges et actions rapportent des gemmes. Dépensez-les dans la boutique : habillages pour votre liste, cadres pour votre photo, effets pour votre compte à rebours. Parrainez vos proches pour en gagner plus.' },
            { targets: [{ sel: '[data-user-menu] [data-open="child-new-dialog"]', menu: true }], icon: 'layer-group', eyebrow: 'Pour toute la famille', title: 'Les listes secondaires', text: 'Créez une liste pour un enfant ou un proche sans compte, et gérez-la à plusieurs. Ici, vous voyez ce qui est déjà offert pour éviter les doublons.' },
            { targets: [{ sel: '[data-user-menu] [data-open="my-gifts-dialog"]', menu: true }], icon: 'gift', eyebrow: 'Vos cadeaux', title: 'Les cadeaux que vous offrez', text: 'Tous les cadeaux que vous avez réservés pour les autres, rassemblés au même endroit.' },
            { targets: [{ sel: '[data-user-menu] [data-open="extension-dialog"]', menu: true }], icon: 'puzzle-piece', eyebrow: 'Encore plus rapide', title: 'L’extension Chrome', text: 'Ajoutez un produit à votre liste en un clic, depuis la page de la boutique.' },
            { targets: ['.note'], icon: 'pen', eyebrow: 'Un petit mot', title: 'Votre message', text: 'Ce mot s’affiche en bas de votre liste : remerciements, tailles, préférences… Modifiez-le avec le crayon.' },
            { icon: 'circle-check', eyebrow: 'C’est tout !', title: 'À vous de jouer', text: 'Commencez par ajouter une première idée. Vous retrouverez cette visite dans le menu du compte.', next: 'Ajouter une idée', prev: 'Terminer', final: true },
        ];
        const bubble = $('[data-tour-bubble]', onboarding);
        const spotlight = $('[data-tour-spotlight]', onboarding);
        const arrow = $('[data-tour-arrow]', onboarding);
        const prevButton = $('[data-tour-prev]', onboarding);
        const nextButton = $('[data-tour-next]', onboarding);
        const accountMenu = $('[data-user-menu]');
        const iconHref = $('svg use')?.getAttribute('href')?.replace(/#.*$/, '') ?? '';
        let steps = [];
        let index = 0;
        let target = null;
        let finished = true;
        let lastFocus = null;

        const visible = (element) => element && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';

        function setMenu(open) {
            if (accountMenu) accountMenu.open = open;
        }

        // Premier élément visible de l'étape (le menu reste ouvert s'il le faut). null : étape sans cible.
        function resolve(step) {
            for (const item of step.targets) {
                const { sel, menu } = typeof item === 'string' ? { sel: item, menu: false } : item;
                setMenu(menu);
                const element = $$(sel).find(visible);
                if (element) return element;
            }
            setMenu(false);
            return undefined;
        }

        function place() {
            if (finished) return;
            const gap = 14;
            const margin = 12;
            bubble.style.left = bubble.style.top = '';

            if (!target) {
                onboarding.classList.add('is-centered');
                return;
            }
            onboarding.classList.remove('is-centered');

            const box = target.getBoundingClientRect();
            const pad = 6;
            const radius = Math.min(parseFloat(getComputedStyle(target).borderTopLeftRadius) || 0, box.height / 2) + pad;
            Object.assign(spotlight.style, {
                left: `${box.left - pad}px`,
                top: `${box.top - pad}px`,
                width: `${box.width + pad * 2}px`,
                height: `${box.height + pad * 2}px`,
                borderRadius: `${radius}px`,
            });

            // Sous la cible s'il y a la place, sinon au-dessus, sinon par-dessus en bas de l'écran.
            const size = bubble.getBoundingClientRect();
            const below = box.bottom + pad + gap + size.height <= innerHeight - margin;
            const above = box.top - pad - gap - size.height >= margin;
            const left = Math.min(Math.max(margin, box.left + box.width / 2 - size.width / 2), innerWidth - size.width - margin);
            let top;
            if (below) top = box.bottom + pad + gap;
            else if (above) top = box.top - pad - gap - size.height;
            else top = innerHeight - size.height - margin;
            bubble.style.left = `${left}px`;
            bubble.style.top = `${top}px`;
            arrow.hidden = !below && !above;
            bubble.dataset.side = below ? 'below' : 'above';
            arrow.style.left = `${Math.min(Math.max(22, box.left + box.width / 2 - left), size.width - 22)}px`;
        }

        // Fait défiler pour que la cible et la bulle tiennent ensemble à l'écran : la cible juste sous la barre
        // du haut (collante), la bulle en dessous. Sur petit écran, une grande cible est montrée par son haut.
        function scrollToTarget() {
            if (target.closest('.topbar, .friends, [data-user-menu]')) return; // toujours à l'écran
            const box = target.getBoundingClientRect();
            const bubbleHeight = bubble.offsetHeight;
            const top = ($('.topbar')?.getBoundingClientRect().bottom ?? 0) + 16;
            const bottom = innerHeight - 12;
            const fits = (box.top >= top && box.bottom + 20 + bubbleHeight <= bottom) || (box.top - 20 - bubbleHeight >= top && box.bottom <= bottom);
            if (fits) return;
            const delta = box.top - top;
            // Long trajet (pied de page…) : direct, sinon la bulle arriverait avant la cible.
            const smooth = Math.abs(delta) < innerHeight && !matchMedia('(prefers-reduced-motion: reduce)').matches;
            scrollBy({ top: delta, behavior: smooth ? 'smooth' : 'instant' }); // « auto » suivrait le scroll-behavior: smooth du CSS
        }

        function render() {
            const step = steps[index];
            target = step.targets ? resolve(step) : null;
            if (!step.targets) setMenu(false);
            // Élément disparu depuis le début (idée supprimée, fenêtre de taille différente…) : étape suivante.
            if (undefined === target) return go(index + 1);

            $('[data-tour-icon]', onboarding).innerHTML = `<svg class="icon" aria-hidden="true"><use href="${iconHref}#i-${step.icon}"></use></svg>`;
            $('[data-tour-eyebrow]', onboarding).textContent = step.eyebrow;
            $('[data-tour-title]', onboarding).textContent = step.title;
            $('[data-tour-text]', onboarding).textContent = step.text;
            $('[data-tour-count]', onboarding).textContent = `${index + 1} / ${steps.length}`;
            $('[data-tour-progress]', onboarding).style.width = `${((index + 1) / steps.length) * 100}%`;
            nextButton.textContent = step.next ?? 'Suivant';
            prevButton.textContent = step.prev ?? 'Précédent';
            bubble.classList.toggle('tour__bubble--intro', !step.targets);

            // Relance l'animation d'apparition de la bulle.
            bubble.classList.remove('is-entering');
            void bubble.offsetWidth;
            bubble.classList.add('is-entering');

            if (target) scrollToTarget();
            place();
            bubble.focus({ preventScroll: true });
        }

        function go(next) {
            if (next < 0) return;
            if (next >= steps.length) return finish();
            index = next;
            render();
        }

        function finish(addIdea = false) {
            if (finished) return;
            finished = true;
            onboarding.hidden = true;
            document.body.classList.remove('is-touring');
            setMenu(false);
            document.dispatchEvent(new Event('kdo:tour-end')); // la fenêtre « nouveau badge » attendait la fin
            // L'écriture est volontairement discrète : un échec n'empêche pas de continuer.
            post('actions/onboardingSeen.php', new FormData()).catch(() => {});
            if (addIdea) $('.card--add')?.click();
            else lastFocus?.focus?.({ preventScroll: true });
        }

        function start() {
            if (!onboarding.hasAttribute('data-tour-here')) {
                // La visite présente sa propre liste : on y va d'abord.
                location.href = `${onboarding.dataset.tourHome}#visite`;
                return;
            }
            // Les étapes dont rien n'est à l'écran sont retirées dès le départ, pour un compteur juste.
            steps = tourSteps.filter((step) => !step.targets || resolve(step));
            setMenu(false);
            lastFocus = document.activeElement;
            finished = false;
            onboarding.hidden = false;
            document.body.classList.add('is-touring');
            go(0);
        }

        nextButton.addEventListener('click', () => {
            const step = steps[index];
            if (step.final) finish(true);
            else go(index + 1);
        });
        prevButton.addEventListener('click', () => {
            if (0 === index || steps[index].final) finish();
            else go(index - 1);
        });
        $$('[data-tour-skip]', onboarding).forEach((button) => button.addEventListener('click', () => finish()));

        document.addEventListener('keydown', (event) => {
            if (finished) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                finish();
            } else if (event.key === 'ArrowRight') {
                go(index + 1);
            } else if (event.key === 'ArrowLeft') {
                go(index - 1);
            } else if (event.key === 'Tab') {
                // Le focus reste dans la bulle.
                const focusable = $$('button', bubble).filter(visible);
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && (document.activeElement === first || document.activeElement === bubble)) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }, true);

        $$('[data-start-onboarding]').forEach((button) => button.addEventListener('click', start));
        addEventListener('resize', place);
        addEventListener('scroll', place, true);

        // Démarrage automatique (première fois) ou demandé depuis une autre page (#visite) :
        // on attend qu'aucune fenêtre ne soit ouverte (badge obtenu, question secrète…).
        const startWhenIdle = () => {
            const open = $('dialog[open]');
            if (open) open.addEventListener('close', () => setTimeout(startWhenIdle, 300), { once: true });
            else start();
        };
        if ('#visite' === location.hash) {
            history.replaceState(null, '', location.pathname + location.search);
            setTimeout(startWhenIdle, 300);
        } else if (onboarding.hasAttribute('data-auto-start') && !navigator.webdriver) {
            setTimeout(startWhenIdle, 700);
        }
    }
}
