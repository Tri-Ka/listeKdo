# Usage : python cut-skins-theme.py <type> depuis docker/ (Pillow, numpy, scipy), sources dans
# ../listeKdo/img/skins-sources/<type>/<skin>.png ; puis copier out/<skin>/<type> vers ../listeKdo/img/skins/<skin>/.
# Planches d'un type de liste (une par habillage, éléments bien espacés) : pancarte = bloc le plus large de la moitié haute,
# les plus grands autres éléments = décorations d1..d5. Rapport des éléments touchant un bord.
from PIL import Image
import numpy as np
from scipy import ndimage
import os, shutil, json, sys
THEME = sys.argv[1] if len(sys.argv) > 1 else 'mariage'  # type de liste : mariage, noel…
SRC = os.environ.get('SRC', '../listeKdo/img/skins-sources')
HINTS = {
    'birthday': {'elegance': (296, 150)},
    'naissance': {'elegance': (800, 150)},
}
ORDER = ['pastel', 'elegance', 'calligraphie', 'steampunk', 'cosmique', 'neon', 'zombie', 'farwest', 'pirate']

def save(img, sl, mask, path, limit):
    arr = np.array(img)[sl].copy()
    arr[..., 3] = np.where(mask, arr[..., 3], 0)
    el = Image.fromarray(arr)
    el = el.crop(el.getbbox())
    if max(el.size) > limit:
        r = limit / max(el.size); el = el.resize((round(el.width * r), round(el.height * r)), Image.LANCZOS)
    el.quantize(colors=256, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE).save(path, optimize=True)

report = {}
shutil.rmtree('out', ignore_errors=True)
for i, skin in enumerate(ORDER):
    im = Image.open(f'{SRC}/{THEME}/{skin}.png').convert('RGBA')
    W, H = im.size
    a = np.array(im)[..., 3] > 40

    def blocks(dil, min_area):
        lab, n = ndimage.label(ndimage.binary_dilation(a, iterations=dil))
        found = []
        for j, sl in enumerate(ndimage.find_objects(lab)):
            m = (lab[sl] == j + 1) & a[sl]
            if m.sum() >= min_area:
                found.append({'sl': sl, 'mask': m, 'area': int(m.sum()), 'x0': sl[1].start, 'y0': sl[0].start,
                              'w': sl[1].stop - sl[1].start, 'h': sl[0].stop - sl[0].start})
        return found

    # Titre : le bloc le plus large de la moitié haute, avec un regroupement large (lettres calligraphiées
    # séparées), mais pas trop haut (sinon ce n'est pas une pancarte).
    for dil in (6, 4, 2, 1):
        coarse = blocks(dil, W * H * 0.004)
        top = [c for c in coarse if c['y0'] + c['h'] / 2 < H / 2 and c['h'] < 0.6 * H and c['h'] < 0.8 * c['w']]
        if top:
            break
    title = max(top, key=lambda c: c['w']) if top else None
    # Détection trompée (un groupe plus large que la pancarte) : un point de la pancarte, en pixels de la planche.
    hint = HINTS.get(THEME, {}).get(skin)
    if hint:
        hx, hy = hint
        for dil in (6, 4, 2, 1):
            inside = [c for c in blocks(dil, W * H * 0.004)
                      if c['x0'] <= hx < c['x0'] + c['w'] and c['y0'] <= hy < c['y0'] + c['h'] and c['mask'][hy - c['y0'], hx - c['x0']]]
            if inside and inside[0]['h'] < 0.8 * inside[0]['w']:
                title = inside[0]
                break
        else:
            # Pancarte soudée à ses voisins : érosion jusqu'à isoler le noyau sous le point, puis reconstruction.
            lab, _ = ndimage.label(a)
            block = lab == lab[hy, hx]
            for k in range(2, 80, 2):
                core_lab, _ = ndimage.label(ndimage.binary_erosion(block, iterations=k))
                core = core_lab[hy, hx]
                if not core:
                    break
                region = core_lab == core
                ys, xs = np.where(region)
                if ys.max() - ys.min() < 0.8 * (xs.max() - xs.min()) and (ys.max() - ys.min()) < 0.5 * H:
                    grown = ndimage.binary_dilation(region, iterations=k + 2) & block
                    others = (core_lab > 0) & ~region
                    _, (iy, ix) = ndimage.distance_transform_edt(~((core_lab > 0)), return_indices=True)
                    mine = grown & (core_lab[iy, ix] == core)
                    ys, xs = np.where(mine)
                    sl = (slice(ys.min(), ys.max() + 1), slice(xs.min(), xs.max() + 1))
                    title = {'sl': sl, 'mask': mine[sl], 'area': int(mine.sum()), 'x0': xs.min(), 'y0': ys.min(),
                             'w': xs.max() - xs.min() + 1, 'h': ys.max() - ys.min() + 1}
                    # Le reste du bloc soudé redevient disponible comme décorations (séparées de la même façon).
                    a = a & ~mine
                    break
    # Décorations : regroupement fin, hors de la zone du titre.
    ty0, ty1, tx0, tx1 = title['y0'], title['y0'] + title['h'], title['x0'], title['x0'] + title['w']
    comps = [c for c in blocks(1, W * H * 0.004)
             if not (c['y0'] >= ty0 - 2 and c['y0'] + c['h'] <= ty1 + 2 and c['x0'] >= tx0 - 2 and c['x0'] + c['w'] <= tx1 + 2)]
    rest = sorted(comps, key=lambda c: -c['area'])
    out = f'out/{skin}/{THEME}'
    os.makedirs(out)
    save(im, title['sl'], title['mask'], f'{out}/title.png', 900)
    for k, c in enumerate(rest[:5]):
        save(im, c['sl'], c['mask'], f'{out}/d{k + 1}.png', 520)
    comps = comps + [title]
    touch = [c for c in comps if c['x0'] <= 1 or c['y0'] <= 1 or c['x0'] + c['w'] >= W - 1 or c['y0'] + c['h'] >= H - 1]
    report[skin] = {'éléments': len(comps), 'titre': f"{title['w']}x{title['h']}", 'au bord': len(touch)}
print(json.dumps(report, ensure_ascii=False, indent=0))
