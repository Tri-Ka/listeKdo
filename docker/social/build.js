/*
 * Visuels de partage : composition HTML/CSS avec les illustrations du site.
 * Aucun compte ni contenu utilisateur. Sorties JPEG 1200 × 630 et icônes.
 */
const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '../..');
const site = path.join(root, 'listeKdo');
const output = path.join(site, 'img/social');
const logo = fs.readFileSync(path.join(site, 'favicon.svg'), 'utf8');
const source = (theme, name) => 'file://' + path.join(site, 'img/deco', theme, name + '.png');
const variants = [
    { key: 'home', label: 'Vos envies, leurs belles attentions', title: 'Le plaisir<br>de faire plaisir.', copy: 'Créez une liste. Partagez vos envies.<br>Gardez la surprise.', theme: 'birthday', main: 'gifts', extra: 'star', color: '#ff5a49', pastel: '#ffe5d6' },
    { key: 'birthday', label: 'Liste d’anniversaire', title: 'Ses envies.<br>Votre attention.', copy: 'Les bons cadeaux, au bon moment.<br>Et toujours la surprise.', theme: 'birthday', main: 'gifts', extra: 'balloons', color: '#de9140', pastel: '#ffe8b7' },
    { key: 'noel', label: 'Liste de Noël', title: 'La magie<br>de faire plaisir.', copy: 'Des envies à glisser sous le sapin.<br>Des surprises à partager entre proches.', theme: 'noel', main: 'gifts', extra: 'gingerbread', color: '#a43c3c', pastel: '#f7ded6' },
    { key: 'naissance', label: 'Liste de naissance', title: 'De petites envies.<br>Un grand bonheur.', copy: 'Toutes les belles attentions<br>pour accueillir bébé.', theme: 'naissance', main: 'teddy', extra: 'rattle', color: '#8471be', pastel: '#e9def6' },
    { key: 'mariage', label: 'Liste de mariage', title: 'De belles envies.<br>Une nouvelle vie.', copy: 'Un oui, mille attentions.<br>Des cadeaux pour écrire la suite.', theme: 'mariage', main: 'rings', extra: 'bouquet', color: '#9b8151', pastel: '#f1e6cd' },
    { key: 'wishlist', label: 'Liste d’envies', title: 'Vos envies,<br>tout simplement.', copy: 'Une idée, une envie, une belle attention.<br>À offrir quand vous voulez.', theme: 'wishlist', main: 'gifts', extra: 'heart', color: '#4c8976', pastel: '#dbede0' },
    { key: 'private', label: 'Partagée en toute discrétion', title: 'Une liste privée.<br>Une belle attention.', copy: 'Vos envies restent entre vous<br>et les proches que vous invitez.', theme: 'wishlist', main: 'gifts', extra: 'heart', color: '#687397', pastel: '#e2e6f3', private: true },
];

function html(v) {
    const art = '<div class="art"><div class="halo"></div><img class="main-art" src="' + source(v.theme, v.main) + '" alt=""><img class="extra-art" src="' + source(v.theme, v.extra) + '" alt=""><div class="note">' + (v.private ? 'Sur invitation' : 'Une liste.<br>Toutes vos envies.') + '</div></div>';
    return [
        '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">',
        '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">',
        '<style>',
        '*{box-sizing:border-box}body{width:1200px;height:630px;margin:0;overflow:hidden;color:#242640;font-family:Outfit,Arial,sans-serif;background:radial-gradient(ellipse at 95% 15%,var(--pastel),transparent 64%),#fff8ee}',
        '.brand{position:absolute;top:56px;left:64px;display:flex;align-items:center;gap:15px;font-size:28px;font-weight:800;letter-spacing:-.8px}.brand svg{width:48px;height:48px;filter:drop-shadow(0 5px 10px #ff5a4930)}',
        '.copy{position:absolute;left:64px;top:154px;width:700px;z-index:2}.label{margin:0 0 20px;color:var(--accent);font-size:17px;letter-spacing:2px;text-transform:uppercase;font-weight:700}',
        'h1{font-size:66px;line-height:1.06;letter-spacing:-2.8px;font-weight:800;margin:0 0 25px}.description{font-size:25px;line-height:1.45;color:#676b85;margin:0;letter-spacing:-.3px}',
        '.art{position:absolute;width:410px;height:440px;right:24px;top:103px}.halo{position:absolute;width:355px;height:355px;left:34px;top:42px;border:1px solid #fff9;border-radius:50%;background:linear-gradient(145deg,#fffffff2,var(--pastel));box-shadow:0 25px 65px #2527440a}',
        '.main-art{position:absolute;width:325px;height:300px;object-fit:contain;left:48px;top:82px;filter:drop-shadow(0 18px 15px #35374418);transform:rotate(-7deg)}',
        '.extra-art{position:absolute;width:130px;height:170px;object-fit:contain;right:0;top:8px;filter:drop-shadow(0 10px 9px #35374414);transform:rotate(12deg)}',
        '.note{position:absolute;bottom:2px;right:33px;background:#fff;border:1px solid #f2eee6;border-radius:18px;padding:17px 23px;font-size:19px;font-weight:600;line-height:1.3;box-shadow:0 10px 30px #25274410;transform:rotate(5deg)}',
        '.footer{position:absolute;left:64px;right:64px;bottom:45px;display:flex;align-items:center;gap:12px}.pill{padding:10px 16px;border:1px solid #2527440d;border-radius:999px;background:#ffffffb8;font-size:15px;font-weight:500;color:#626780}.domain{margin-left:auto;font-size:17px;color:#767a90;font-weight:500}',
        '</style></head><body style="--accent:' + v.color + ';--pastel:' + v.pastel + '">',
        '<div class="brand">' + logo + '<span>ListeKdo</span></div>',
        '<div class="copy"><p class="label">' + v.label + '</p><h1>' + v.title + '</h1><p class="description">' + v.copy + '</p></div>',
        art,
        '<div class="footer"><span class="pill">Entre proches</span><span class="pill">Sans doublons</span><span class="pill">Surprise préservée</span><span class="domain">liste-kdo.fr</span></div>',
        '</body></html>',
    ].join('');
}

(async () => {
    fs.mkdirSync(output, { recursive: true });
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
        const preview = path.join(__dirname, '.preview.html');
        try {
            for (const variant of variants) {
                fs.writeFileSync(preview, html(variant));
                await page.goto('file://' + preview);
                await page.evaluate(async () => {
                    await document.fonts.ready;
                    await Promise.all([...document.images].map((img) => img.decode()));
                });
                await page.screenshot({ path: path.join(output, variant.key + '.jpg'), type: 'jpeg', quality: 90 });
                console.log('Image : ' + variant.key + ' (1200 × 630)');
            }
        } finally {
            fs.rmSync(preview, { force: true });
        }

        const icons = [];
        for (const size of [16, 32, 48, 180, 192, 512]) {
            await page.setViewportSize({ width: size, height: size });
            await page.setContent('<style>*{margin:0}svg{display:block;width:100vw;height:100vh}</style>' + logo);
            const png = await page.screenshot({ omitBackground: true });
            if (size <= 48) icons.push({ size, png });
            const name = size === 180 ? 'apple-touch-icon.png' : 'favicon-' + size + '.png';
            if (size >= 32) fs.writeFileSync(path.join(site, name), png);
        }
        const directory = Buffer.alloc(6 + 16 * icons.length);
        directory.writeUInt16LE(1, 2);
        directory.writeUInt16LE(icons.length, 4);
        let offset = directory.length;
        icons.forEach(({ size, png }, i) => {
            const entry = 6 + i * 16;
            directory[entry] = size;
            directory[entry + 1] = size;
            directory.writeUInt16LE(1, entry + 4);
            directory.writeUInt16LE(32, entry + 6);
            directory.writeUInt32LE(png.length, entry + 8);
            directory.writeUInt32LE(offset, entry + 12);
            offset += png.length;
        });
        fs.writeFileSync(path.join(site, 'favicon.ico'), Buffer.concat([directory, ...icons.map((icon) => icon.png)]));
        console.log('Favicon : SVG, ICO (16/32/48), PNG et icône Apple');
    } finally {
        await browser.close();
    }
})().catch((error) => { console.error(error); process.exit(1); });
