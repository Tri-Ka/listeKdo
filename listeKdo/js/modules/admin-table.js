/* Recherche, tri et pagination des petits tableaux déjà chargés dans l’administration. */
export function initAdminTable(table, {
    prefix = 'gem', numericSort = ['amount', 'default'], filters = [],
    searchText = (row) => row.textContent,
} = {}) {
    const find = (name) => table.querySelector(`[data-${prefix}-${name}]`);
    const rows = [...table.querySelectorAll(`[data-${prefix}-row]`)];
    const search = find('search');
    const per = find('per');
    const sortButtons = [...table.querySelectorAll(`[data-${prefix}-sort]`)];
    const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr');
    const collator = new Intl.Collator('fr', { numeric: true, sensitivity: 'base' });
    let page = 1;
    let sort = 'name';
    let direction = 1;
    table.querySelectorAll(`[data-${prefix}-controls]`).forEach((control) => { control.hidden = false; });

    function render() {
        const query = normalize(search.value.trim());
        const filtered = rows.filter((row) => filters.every(({ element, key }) => !element.value || row.dataset[key] === element.value)
            && normalize(searchText(row)).includes(query));
        filtered.sort((a, b) => direction * (numericSort.includes(sort)
            ? Number(a.dataset[sort]) - Number(b.dataset[sort])
            : collator.compare(a.dataset[sort], b.dataset[sort])));
        const size = Number(per.value);
        const pages = Math.max(1, Math.ceil(filtered.length / size));
        page = Math.min(page, pages);
        rows.forEach((row) => {
            row.hidden = true;
        });
        const from = (page - 1) * size;
        filtered.slice(from, from + size).forEach((row) => {
            row.hidden = false;
            table.querySelector('tbody').insertBefore(row, find('empty'));
        });
        find('empty').hidden = filtered.length !== 0;
        if (filtered.length) {
            const range = document.createElement('strong');
            range.textContent = `${from + 1}–${Math.min(from + size, filtered.length)}`;
            find('count').replaceChildren(range, document.createTextNode(` sur ${filtered.length}`));
        } else find('count').textContent = 'Aucun résultat';
        find('page').textContent = `${page} / ${pages}`;
        find('prev').disabled = page === 1;
        find('next').disabled = page === pages;
        find('prev').classList.toggle('is-disabled', page === 1);
        find('next').classList.toggle('is-disabled', page === pages);
        find('pagination').hidden = pages === 1;
        const numbers = [...new Set([1, page - 1, page, page + 1, pages])].filter((number) => number > 0 && number <= pages).sort((a, b) => a - b);
        const pager = find('pages');
        pager.replaceChildren();
        let last = 0;
        numbers.forEach((number) => {
            if (number - last > 1) {
                const gap = document.createElement('span');
                gap.className = 'dt__page dt__page--gap';
                gap.textContent = '…';
                gap.setAttribute('aria-hidden', 'true');
                pager.append(gap);
            }
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dt__page' + (number === page ? ' is-current' : '');
            button.dataset[`${prefix}PageNumber`] = number;
            button.textContent = number;
            if (number === page) button.setAttribute('aria-current', 'page');
            pager.append(button);
            last = number;
        });
        sortButtons.forEach((button) => {
            const active = button.dataset[`${prefix}Sort`] === sort;
            button.closest('th').setAttribute('aria-sort', active ? (direction === 1 ? 'ascending' : 'descending') : 'none');
            button.classList.toggle('is-active', active);
            const icon = button.querySelector(`[data-${prefix}-sort-icon] use`);
            const iconName = active ? (direction === 1 ? 'sort-up' : 'sort-down') : 'sort';
            icon.setAttribute('href', icon.getAttribute('href').replace(/#i-[^#]+$/, `#i-${iconName}`));
        });
    }

    search.addEventListener('input', () => { page = 1; render(); });
    filters.forEach(({ element }) => element.addEventListener('change', () => { page = 1; render(); }));
    per.addEventListener('change', () => { page = 1; render(); });
    find('prev').addEventListener('click', () => { page--; render(); });
    find('next').addEventListener('click', () => { page++; render(); });
    find('pages').addEventListener('click', (event) => {
        const button = event.target.closest(`[data-${prefix}-page-number]`);
        if (!button) return;
        page = Number(button.dataset[`${prefix}PageNumber`]);
        render();
    });
    sortButtons.forEach((button) => button.addEventListener('click', () => {
        const column = button.dataset[`${prefix}Sort`];
        direction = sort === column ? -direction : 1;
        sort = column;
        page = 1;
        render();
    }));
    render();
    return { refresh: render };
}
