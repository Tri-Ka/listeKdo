/* da.gd accepte CORS : l'appel part du navigateur lorsque Free bloque les sorties réseau. */
const requests = new Map();

export async function resolveShortLink(data) {
    const long = data.long || data.url;
    if (!data.browser || data.url !== long) return data.url;
    if (data.browser.provider !== 'dagd' || data.browser.endpoint !== 'https://da.gd/s?url='
        || !data.browser.site || !long?.startsWith(data.browser.site)) return long;
    if (requests.has(long)) return requests.get(long);

    const request = (async () => {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 6000);
        try {
            const response = await fetch('https://da.gd/s?url=' + encodeURIComponent(long), {
                credentials: 'omit',
                referrerPolicy: 'no-referrer',
                signal: controller.signal,
            });
            if (!response.ok) return long;
            const short = (await response.text()).trim();
            return /^https?:\/\/da\.gd\/[A-Za-z0-9_-]+$/.test(short)
                ? short.replace(/^http:/, 'https:') : long;
        } catch {
            return long;
        } finally {
            clearTimeout(timer);
        }
    })();
    requests.set(long, request);
    const short = await request;
    if (short === long) requests.delete(long);
    return short;
}
