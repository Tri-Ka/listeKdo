import { initAdminTable } from './admin-table.js';

export function initGemTables({ post, toast }) {
    const root = document.querySelector('[data-gem-admin]');
    if (!root) return;
    const tables = [...root.querySelectorAll('[data-gem-table]')];
    const links = [...root.querySelectorAll('[data-gem-section]')];
    const dialog = document.getElementById('gem-edit-dialog');
    const form = dialog.querySelector('[data-gem-form]');
    const input = form.elements.amount;
    const button = form.querySelector('button[type=submit]');
    let editingRow;
    let definition;
    const controllers = new Map();
    const showSection = () => {
        const selected = links.find((link) => link.hash === location.hash) || links[0];
        links.forEach((link) => {
            if (link === selected) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
        tables.forEach((table) => { table.hidden = table.dataset.gemTable !== selected.dataset.gemSection; });
    };
    showSection();
    window.addEventListener('hashchange', showSection);
    tables.forEach((table) => controllers.set(table, initAdminTable(table, {
        numericSort: ['amount', 'default', 'rarity'],
        filters: [{ element: table.querySelector('[data-gem-category]'), key: 'category' }],
    })));
    root.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-gem-edit]');
        if (!edit || edit.disabled || form.hasAttribute('aria-busy')) return;
        editingRow = edit.closest('[data-gem-row]');
        definition = JSON.parse(editingRow.dataset.gemDefinition);
        input.value = definition.value;
        input.min = definition.min;
        input.max = definition.max;
        dialog.querySelector('[data-gem-label]').textContent = definition.label;
        dialog.querySelector('[data-gem-hint]').textContent = `Par défaut : ${definition.default} gemmes.`
            + (definition.name === 'badge_gems' ? ' 0 = récompense automatique selon le type et le niveau.' : '');
        dialog.showModal();
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled || !editingRow) return;
        const value = Number(input.value);
        const body = new FormData(form);
        body.delete('amount');
        body.set(definition.name, value);
        for (const [key, hidden] of Object.entries(definition.hidden)) body.set(key, hidden);
        button.disabled = true;
        input.readOnly = true;
        form.setAttribute('aria-busy', 'true');
        try {
            const data = await post(form.action, body);
            definition.value = value;
            editingRow.dataset.gemDefinition = JSON.stringify(definition);
            const automatic = definition.name === 'badge_gems' && value === 0;
            const effective = automatic ? definition.default : value;
            editingRow.dataset.amount = effective;
            editingRow.querySelector('[data-gem-amount]').textContent = effective;
            editingRow.querySelector('[data-gem-auto]').textContent = automatic ? 'Automatique' : '';
            controllers.get(editingRow.closest('[data-gem-table]')).refresh();
            dialog.close();
            toast(data.message || 'Montant enregistré.', 'success');
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            button.disabled = false;
            input.readOnly = false;
            form.removeAttribute('aria-busy');
        }
    });
}
