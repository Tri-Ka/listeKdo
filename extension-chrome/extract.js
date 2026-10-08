/*
 * Lecture des informations produit de la page visitée.
 * Cette fonction est injectée telle quelle dans l'onglet (chrome.scripting.executeScript) :
 * elle ne doit dépendre de rien d'extérieur.
 */
function extractProduct() {
    const absolute = (url) => {
        try {
            return url ? new URL(url, location.href).href : '';
        } catch {
            return '';
        }
    };
    // Certains sites renvoient un texte encore encodé comme une URL (« Four%20Seasons »),
    // en entités HTML (« &amp; ») ou avec des balises (« <p>… »). Fins de paragraphe et <br> deviennent des retours à la ligne.
    const decode = (text) => {
        let value = String(text || '');
        if (/%[0-9a-f]{2}/i.test(value)) {
            try {
                value = decodeURIComponent(value);
            } catch {
                // « 50% de réduction » : pas un encodage, on garde le texte.
            }
        }
        // Deux passes : des balises échappées (« &lt;p&gt; ») redeviennent des balises à la première.
        for (let pass = 0; pass < 2 && /<\/?[a-z][^>]*>|&(#\d+|#x[0-9a-f]+|[a-z]+);/i.test(value); pass++) {
            const html = value.replace(/<br\s*\/?>|<\/(p|div|li|h[1-6])>/gi, '$&\n');
            value = new DOMParser().parseFromString(html, 'text/html').body.textContent;
        }
        return value;
    };
    const clean = (text) => decode(text).replace(/\s+/g, ' ').trim();
    // Comme clean(), en gardant les retours à la ligne (une ligne vide au plus) : pour la description.
    const cleanLines = (text) => decode(text).replace(/[^\S\n]+/g, ' ').replace(/ *\n */g, '\n').replace(/\n{3,}/g, '\n\n').trim();
    const meta = (...names) => {
        for (const name of names) {
            const element = document.querySelector(`meta[property="${name}"], meta[name="${name}"], meta[itemprop="${name}"]`);
            if (element?.content) return clean(element.content);
        }
        return '';
    };

    // Données structurées schema.org (la source la plus fiable sur les boutiques).
    let product = null;
    const visit = (node) => {
        if (!node || typeof node !== 'object' || product) return;
        if (Array.isArray(node)) return node.forEach(visit);
        const type = [].concat(node['@type'] || []).join(' ');
        if (/\bProduct\b/i.test(type)) {
            product = node;
            return;
        }
        visit(node['@graph']);
        visit(node.mainEntity);
        visit(node.itemListElement);
    };
    document.querySelectorAll('script[type="application/ld+json"]').forEach((script) => {
        try {
            visit(JSON.parse(script.textContent));
        } catch {
            // JSON invalide sur certains sites : on l'ignore.
        }
    });

    const ldImages = [].concat(product?.image || []).map((image) => (typeof image === 'string' ? image : image?.url || image?.contentUrl));
    const offer = [].concat(product?.offers || [])[0] || {};
    const price = offer.price || offer.lowPrice || offer.priceSpecification?.price || meta('product:price:amount', 'og:price:amount', 'price');
    const currency = offer.priceCurrency || meta('product:price:currency', 'og:price:currency', 'priceCurrency') || 'EUR';

    // Images : celles déclarées par le site, puis les grandes images visibles de la page.
    const candidates = [
        ...ldImages,
        meta('og:image:secure_url', 'og:image', 'twitter:image', 'twitter:image:src'),
        document.querySelector('link[rel="image_src"]')?.href,
    ];
    [...document.images]
        .filter((image) => image.naturalWidth >= 250 && image.naturalHeight >= 200 && image.getBoundingClientRect().width > 0)
        .sort((a, b) => b.naturalWidth * b.naturalHeight - a.naturalWidth * a.naturalHeight)
        .slice(0, 8)
        .forEach((image) => candidates.push(image.currentSrc || image.src));

    const images = [...new Set(candidates.map(absolute).filter((url) => /^https?:/.test(url) && !/\.svg(\?|$)/i.test(url)))].slice(0, 8);

    // Lien sans paramètres de suivi publicitaire.
    let link = absolute(document.querySelector('link[rel="canonical"]')?.href || meta('og:url')) || location.href;
    try {
        const url = new URL(link);
        [...url.searchParams.keys()].filter((key) => /^(utm_|gclid|fbclid|mc_|ref_?$|tag$)/i.test(key)).forEach((key) => url.searchParams.delete(key));
        link = url.href;
    } catch {
        link = location.href;
    }

    return {
        title: clean(product?.name) || meta('og:title', 'twitter:title') || clean(document.title),
        description: cleanLines(product?.description) || meta('og:description', 'twitter:description', 'description'),
        images,
        price: price ? String(price) : '',
        currency,
        link,
        site: meta('og:site_name') || location.hostname.replace(/^www\./, ''),
    };
}
