# Découpe des planches img/skins-sources/planche-N.png (sN ci-dessous) en img/skins/<skin>/<thème>/.
# Lancer depuis docker/ avec Pillow, numpy et scipy, puis copier skinout/ vers ../listeKdo/img/skins/.
# Découpe des planches de skins : un dossier par skin et par thème (title.png + d1..d5.png), plus un aperçu.
from PIL import Image, ImageDraw
import numpy as np
from scipy import ndimage
import os, json, shutil

THEMES3 = ['birthday', 'noel', 'naissance']
SHEETS3 = {  # planche à 3 colonnes -> skin
    's1': 'elegance', 's2': 'pastel', 's3': 'zombie', 's4': 'calligraphie',
    's5': 'steampunk', 's6': 'neon', 's7': 'cosmique',
}
WISH = {  # skin -> (planche, boîte en coordonnées de l'aperçu à 50 %)
    'elegance': ('s9', (0, 0, 262, 252)),
    'calligraphie': ('s10', (0, 0, 262, 262)),
    'pastel': ('s8', (268, 0, 502, 192)),
    'steampunk': ('s9', (262, 0, 518, 256)),
    'neon': ('s10', (514, 0, 768, 266)),
    'cosmique': ('s9', (0, 256, 266, 512)),
    'pirate': ('s9', (264, 256, 518, 512)),
    'farwest': ('s9', (514, 256, 768, 512)),
    'zombie': ('s8', (380, 372, 680, 512)),
}

def components(img, dil):
    a = np.array(img)[..., 3] > 40
    lab, n = ndimage.label(ndimage.binary_dilation(a, iterations=dil))
    objs = ndimage.find_objects(lab)
    out = []
    for i, sl in enumerate(objs):
        mask = (lab[sl] == i + 1) & a[sl]
        area = int(mask.sum())
        out.append({'sl': sl, 'mask': mask, 'area': area, 'y0': sl[0].start, 'x0': sl[1].start,
                    'w': sl[1].stop - sl[1].start, 'h': sl[0].stop - sl[0].start})
    return out

def save(img, comp, path, limit):
    arr = np.array(img)[comp['sl']].copy()
    arr[..., 3] = np.where(comp['mask'], arr[..., 3], 0)
    el = Image.fromarray(arr)
    if max(el.size) > limit:
        r = limit / max(el.size)
        el = el.resize((round(el.width * r), round(el.height * r)), Image.LANCZOS)
    el.quantize(colors=256, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE).save(path, optimize=True)
    return el

TB = {  # bas du titre (fraction de la hauteur), par planche et par colonne
    's1': [.42, .52, .41], 's2': [.42, .53, .42], 's3': [.45, .40, .40], 's4': [.30, .33, .33],
    's5': [.33, .36, .36], 's6': [.38, .40, .41], 's7': [.42, .45, .42],
}
WTB = {'elegance': .47, 'calligraphie': .58, 'pastel': .53, 'steampunk': .48, 'neon': .48,
       'cosmique': .44, 'pirate': .51, 'farwest': .49, 'zombie': 1.0}

def cut(img, out, tb, dil, many=True):
    W, H = img.size
    y = int(H * tb)
    os.makedirs(out, exist_ok=True)
    # Titre : tout ce qui est au-dessus de la ligne (morceaux complets seulement)
    top = img.crop((0, 0, W, y))
    tcomps = [c for c in components(top, 2) if c['area'] > W * y * 0.002]
    # Morceaux coupés par le haut du cadre (planche voisine) : écartés, sauf s'ils sont grands.
    tcomps = [c for c in tcomps if c['y0'] > 1 or c['area'] > W * y * 0.06]
    arr = np.zeros((y, W, 4), dtype=np.uint8)
    src = np.array(top)
    for c in tcomps:
        sl = c['sl']
        arr[sl][c['mask']] = src[sl][c['mask']]
    t = Image.fromarray(arr)
    t = t.crop(t.getbbox())
    if max(t.size) > 900:
        r = 900 / max(t.size); t = t.resize((round(t.width * r), round(t.height * r)), Image.LANCZOS)
    t.quantize(colors=256, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE).save(f'{out}/title.png', optimize=True)
    # Décorations : sous la ligne
    if y >= H - 2:
        return 0
    bottom = img.crop((0, y, W, H))
    if not many:
        b = bottom.crop(bottom.getbbox())
        if max(b.size) > 620:
            r = 620 / max(b.size); b = b.resize((round(b.width * r), round(b.height * r)), Image.LANCZOS)
        b.quantize(colors=256, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE).save(f'{out}/d1.png', optimize=True)
        return 1
    comps = [c for c in components(bottom, dil) if c['area'] > W * H * 0.006]
    # On écarte les morceaux coupés par les bords de la colonne
    # On écarte les morceaux coupés (bords de la colonne, ligne du titre) et les éclats très fins
    big = W * H * 0.05
    comps = [c for c in comps if (c['area'] > big or (c['x0'] > 2 and c['x0'] + c['w'] < W - 2 and c['y0'] > 2))
             and max(c['w'], c['h']) < 4 * min(c['w'], c['h'])] or comps
    comps = sorted(comps, key=lambda c: -c['area'])[:5]
    for i, c in enumerate(comps):
        save(bottom, c, f'{out}/d{i + 1}.png', 520)
    return len(comps)

shutil.rmtree('skinout', ignore_errors=True)
report = {}

def split_block(parts):
    """Sépare un bloc d'éléments soudés (pancarte + oiseau + bouquet…) : érosion progressive jusqu'à ce que
    le bloc se coupe en plusieurs noyaux, puis chaque pixel revient au noyau le plus proche."""
    y0 = min(c['y0'] for c in parts); x0 = min(c['x0'] for c in parts)
    y1 = max(c['y0'] + c['h'] for c in parts); x1 = max(c['x0'] + c['w'] for c in parts)
    M = np.zeros((y1 - y0, x1 - x0), dtype=bool)
    for c in parts:
        sl = c['sl']
        M[sl[0].start - y0:sl[0].stop - y0, sl[1].start - x0:sl[1].stop - x0] |= c['mask']
    total = M.sum()
    markers = None
    for k in range(2, 60, 2):
        lab, n = ndimage.label(ndimage.binary_erosion(M, iterations=k))
        if n < 2:
            if n == 0: break
            continue
        sizes = ndimage.sum(np.ones_like(lab), lab, range(1, n + 1))
        big = [i + 1 for i, v in enumerate(sizes) if v > total * 0.02]
        if len(big) >= 2:
            markers = np.zeros_like(lab)
            for j, b in enumerate(big):
                markers[lab == b] = j + 1
            break
    if markers is None:
        return None
    _, (iy, ix) = ndimage.distance_transform_edt(markers == 0, return_indices=True)
    owner = markers[iy, ix]
    pieces = []
    for j in range(1, owner.max() + 1):
        m = (owner == j) & M
        if m.sum() < total * 0.03:
            continue
        ys, xs = np.where(m)
        sl = (slice(y0 + ys.min(), y0 + ys.max() + 1), slice(x0 + xs.min(), x0 + xs.max() + 1))
        pieces.append({'sl': sl, 'mask': m[ys.min():ys.max() + 1, xs.min():xs.max() + 1], 'area': int(m.sum()),
                       'y0': sl[0].start, 'x0': sl[1].start, 'w': sl[1].stop - sl[1].start, 'h': sl[0].stop - sl[0].start})
    return pieces if len(pieces) >= 2 else None

def place(img, comps, out, tb_y, many=True, single=False):
    """Éléments entiers (jamais coupés) : titre = ceux dont le centre est au-dessus de tb_y."""
    os.makedirs(out, exist_ok=True)
    original = comps
    # Bloc à cheval sur la ligne du titre (titre collé à une décoration) : on le redécoupe en ses vrais morceaux.
    pieces = []
    for c in comps:
        if c['y0'] < tb_y - 10 and c['y0'] + c['h'] > tb_y + 10:
            lab, n = ndimage.label(c['mask'])
            for j, sl in enumerate(ndimage.find_objects(lab)):
                m = lab[sl] == j + 1
                if m.sum() < 400:
                    continue
                full = (slice(c['sl'][0].start + sl[0].start, c['sl'][0].start + sl[0].stop),
                        slice(c['sl'][1].start + sl[1].start, c['sl'][1].start + sl[1].stop))
                pieces.append({'sl': full, 'mask': m, 'area': int(m.sum()), 'y0': full[0].start, 'x0': full[1].start,
                               'w': full[1].stop - full[1].start, 'h': full[0].stop - full[0].start})
        else:
            pieces.append(c)
    comps = pieces
    title = [c for c in comps if c['y0'] + c['h'] / 2 < tb_y]
    rest = [c for c in comps if c['y0'] + c['h'] / 2 >= tb_y]
    if single and title:
        main = max(title, key=lambda c: c['w'] * c['h'])
        rest = [c for c in title if c is not main] + rest
        title = [main]
    # Un vrai titre est grand : quelques confettis au-dessus de la ligne ne suffisent pas (titre soudé ailleurs).
    if title and max(c['w'] for c in title) < 0.35 * max(c['x0'] + c['w'] for c in comps) - 0.35 * min(c['x0'] for c in comps):
        title = []
    # Titre soudé à une décoration : le bloc entier (jamais coupé) devient le titre.
    if not title:
        above = [c for c in original if c['y0'] < tb_y]
        if above:
            block = max(above, key=lambda c: c['area'])
            title = [block]
            b0, b1 = block['y0'], block['y0'] + block['h']
            x0b, x1b = block['x0'], block['x0'] + block['w']
            # Les morceaux de ce bloc ne servent pas aussi de décorations.
            rest = [c for c in rest if not (b0 <= c['y0'] and c['y0'] + c['h'] <= b1 and x0b <= c['x0'] and c['x0'] + c['w'] <= x1b)]
    src = np.array(img)
    def merge(parts, path, limit):
        y0 = min(c['y0'] for c in parts); x0 = min(c['x0'] for c in parts)
        y1 = max(c['y0'] + c['h'] for c in parts); x1 = max(c['x0'] + c['w'] for c in parts)
        arr = np.zeros((y1 - y0, x1 - x0, 4), dtype=np.uint8)
        for c in parts:
            sl = c['sl']
            region = arr[sl[0].start - y0:sl[0].stop - y0, sl[1].start - x0:sl[1].stop - x0]
            region[c['mask']] = src[sl][c['mask']]
        el = Image.fromarray(arr)
        if max(el.size) > limit:
            r = limit / max(el.size); el = el.resize((round(el.width * r), round(el.height * r)), Image.LANCZOS)
        el.quantize(colors=256, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE).save(path, optimize=True)
    # Titre trop haut pour une pancarte : on le sépare en éléments (pancarte = la plus large, en haut).
    if title:
        tw = max(c['x0'] + c['w'] for c in title) - min(c['x0'] for c in title)
        th = max(c['y0'] + c['h'] for c in title) - min(c['y0'] for c in title)
        # Plusieurs passes : tant que la pancarte reste trop haute, on la resépare.
        for _ in range(4):
            if th <= 0.55 * tw:
                break
            pieces = split_block(title)
            if not pieces:
                break
            widest = max(p['w'] for p in pieces)
            sign = min([p for p in pieces if p['w'] >= 0.6 * widest], key=lambda p: p['y0'])
            title = [sign]
            rest = sorted([p for p in pieces if p is not sign], key=lambda p: -p['area']) + rest
            tw, th = sign['w'], sign['h']
            print('séparé :', out, len(pieces), 'morceaux')
    if title:
        merge(title, f'{out}/title.png', 900)
    if not rest:
        return 0
    if not many:
        extra = [c for c in rest if 'mask' in c and c.get('split')]
        merge(rest, f'{out}/d1.png', 620)
        return 1
    rest = [c for c in rest if max(c['w'], c['h']) < 4 * min(c['w'], c['h']) and c['area'] > img.size[0] * img.size[1] * 0.002]
    rest = sorted(rest, key=lambda c: -c['area'])[:5]
    for i, c in enumerate(rest):
        merge([c], f'{out}/d{i + 1}.png', 520)
    return len(rest)

for sheet, skin in SHEETS3.items():
    im = Image.open(f'../listeKdo/img/skins-sources/planche-{sheet[1:]}.png').convert('RGBA')
    W, H = im.size
    comps = [c for c in components(im, 3) if c['area'] > W * H * 0.0008]
    for i, theme in enumerate(THEMES3):
        mine = [c for c in comps if W * i / 3 <= c['x0'] + c['w'] / 2 < W * (i + 1) / 3]
        out = f'skinout/{skin}/{theme}'
        report[f'{skin}/{theme}'] = place(im, mine, out, TB[sheet][i] * H)
for skin, (sheet, box) in WISH.items():
    im = Image.open(f'../listeKdo/img/skins-sources/planche-{sheet[1:]}.png').convert('RGBA')
    W, H = im.size
    x0, y0, x1, y1 = (v * 2 for v in box)
    comps = [c for c in components(im, 3) if c['area'] > W * H * 0.0005]
    mine = [c for c in comps if x0 <= c['x0'] + c['w'] / 2 < x1 and y0 <= c['y0'] + c['h'] / 2 < y1]
    out = f'skinout/{skin}/wishlist'
    report[f'{skin}/wishlist'] = place(im, mine, out, y0 + WTB[skin] * (y1 - y0), False)
# Mariage : planche 3 x 3 (s11), une case par habillage, pancarte « Vive les Mariés ! » en haut à gauche.
MARIAGE = [['pastel', 'elegance', 'calligraphie'], ['steampunk', 'cosmique', 'neon'], ['zombie', 'farwest', 'pirate']]
im = Image.open('../listeKdo/img/skins-sources/planche-11.png').convert('RGBA')
W, H = im.size
comps = [c for c in components(im, 1) if c['area'] > W * H * 0.0006]
for r, row in enumerate(MARIAGE):
    for k, skin in enumerate(row):
        x0, x1, y0, y1 = W * k / 3, W * (k + 1) / 3, H * r / 3, H * (r + 1) / 3
        mine = [c for c in comps if x0 <= c['x0'] + c['w'] / 2 < x1 and y0 <= c['y0'] + c['h'] / 2 < y1]
        report[f'{skin}/mariage'] = place(im, mine, f'skinout/{skin}/mariage', y0 + 0.45 * (y1 - y0), True, True)
print(json.dumps(report))

# Planche de contrôle
rows = []
for d in sorted(os.listdir('skinout')):
    for t in sorted(os.listdir(f'skinout/{d}')):
        rows.append((d, t, sorted(os.listdir(f'skinout/{d}/{t}'))))
cell = 120
sheet = Image.new('RGB', (cell * 7, cell * len(rows)), (70, 70, 70))
dr = ImageDraw.Draw(sheet)
for r, (d, t, files) in enumerate(rows):
    dr.text((4, r * cell + 4), f'{d}\n{t}', fill=(255, 255, 0), font_size=14)
    for c, f in enumerate(['title.png'] + [f for f in files if f != 'title.png']):
        el = Image.open(f'skinout/{d}/{t}/{f}').convert('RGBA'); el.thumbnail((cell - 8, cell - 8))
        sheet.paste(el, (cell * (c + 1) + 4, r * cell + 4), el)
sheet.save('skinout-contact.jpg', quality=75)
