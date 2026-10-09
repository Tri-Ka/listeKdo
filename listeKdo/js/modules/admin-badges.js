import { initAdminTable } from './admin-table.js';

export function initBadgeTable({ post, toast }) {
    const table = document.querySelector('[data-badge-table]');
    const dialog = document.getElementById('badge-edit-dialog');
    if (!table || !dialog) return;
    const defaults = JSON.parse(table.dataset.badgeDefaults);
    const form = dialog.querySelector('.badge-admin__form');
    const button = form.querySelector('button[type=submit]');
    const controller = initAdminTable(table, {
        prefix: 'badge', numericSort: ['level', 'threshold', 'amount', 'holders'],
        filters: [...table.querySelectorAll('[data-badge-filter]')].map((element) => ({ element, key: element.dataset.badgeFilter })),
    });
    const open = (badge) => {
        if (form.hasAttribute('aria-busy')) return;
        for (const field of form.querySelectorAll('input, select')) {
            if (!(field.name in badge)) continue;
            if (field.type === 'checkbox') field.checked = Number(badge[field.name]) === 1;
            else field.value = badge[field.name];
        }
        const isNew = Number(badge.id) === 0;
        dialog.querySelector('h2').textContent = isNew ? 'Ajouter un badge' : 'Modifier un badge';
        button.textContent = isNew ? 'Ajouter le badge' : 'Enregistrer';
        dialog.showModal();
    };
    document.querySelector('[data-badge-create]')?.addEventListener('click', () => open(JSON.parse(dialog.dataset.badgeBlank)));
    table.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-badge-edit]');
        if (edit) open(JSON.parse(edit.closest('[data-badge-row]').dataset.badgeDefinition));
    });
    form.addEventListener('submit', async (event) => {
        // La création garde la redirection du serveur vers le nouveau catalogue.
        if (Number(form.elements.id.value) === 0) return;
        event.preventDefault();
        if (button.disabled) return;
        const body = new FormData(form);
        const fields = [...form.querySelectorAll('input, select')].filter((field) => !field.disabled);
        const row = table.querySelector(`[data-badge-row][data-id="${body.get('id')}"]`);
        const selectedText = (name) => form.elements[name].selectedOptions[0].textContent;
        button.disabled = true;
        fields.forEach((field) => { field.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        try {
            const data = await post(form.action, body);
            const name = body.get('name').trim();
            const tier = body.get('tier');
            const active = body.get('active') === '1';
            const amount = Number(body.get('gems') || 0);
            const effective = body.has('gems') ? (amount || defaults[body.get('kind')][tier]) : 0;
            Object.assign(row.dataset, { name, category: selectedText('kind'), tier, level: form.elements.tier.selectedIndex,
                threshold: body.get('threshold'), amount: effective, state: active ? 'Actif' : 'Désactivé' });
            const definition = JSON.parse(row.dataset.badgeDefinition);
            for (const [key, value] of body) if (key !== '_token') definition[key] = value;
            definition.active = active ? 1 : 0;
            definition.secret = body.get('secret') === '1' ? 1 : 0;
            definition.name = name;
            definition.description = body.get('description').trim();
            row.dataset.badgeDefinition = JSON.stringify(definition);
            const set = (part, value) => { const cell = row.querySelector(`[data-badge-${part}]`); if (cell) cell.textContent = value; };
            set('name', name);
            set('description', definition.description);
            set('kind', selectedText('kind'));
            set('tier', selectedText('tier'));
            set('metric', selectedText('metric'));
            set('threshold', body.get('threshold'));
            set('amount', effective);
            set('auto', amount === 0 ? 'Automatique' : 'Personnalisé');
            set('state', active ? 'Actif' : 'Désactivé');
            set('secret', definition.secret ? 'Secret' : '');
            set('medal', body.get('emoji').trim());
            row.querySelector('[data-badge-medal]').className = `badge-admin__medal medal--${tier}`;
            row.querySelector('[data-badge-tier]').className = `badge-admin__chip medal--${tier}`;
            row.querySelector('[data-badge-state]').classList.toggle('badge-admin__chip--off', !active);
            controller.refresh();
            dialog.close();
            toast(data.message, 'success');
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            fields.forEach((field) => { field.disabled = false; });
            button.disabled = false;
            form.removeAttribute('aria-busy');
        }
    });
}
