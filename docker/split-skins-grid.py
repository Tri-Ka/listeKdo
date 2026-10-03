# Planche unique en grille 3 x 3 (lignes de séparation blanches, étiquette « Pastel »… en haut à gauche de chaque case) :
# découpe chaque case à l'intérieur de la grille, retire l'étiquette, et l'enregistre en <sortie>/<type>/<skin>.png
# pour cut-skins-theme.py. Usage : python split-skins-grid.py planche.png <type> <sortie>
from PIL import Image
import numpy as np
from scipy import ndimage
import sys, os
ORDER = ['pastel', 'elegance', 'calligraphie', 'steampunk', 'cosmique', 'neon', 'zombie', 'farwest', 'pirate']
src, theme, out = sys.argv[1], sys.argv[2], sys.argv[3]
im = Image.open(src).convert('RGBA')
arr = np.array(im)
W, H = im.size
full = arr[..., 3] > 200

def lines(profile, size):
    """Positions des lignes de grille (bandes pleines), regroupées, hors bords."""
    pos = [i for i in range(size) if profile[i] > 0.8]
    groups, cur = [], []
    for p in pos:
        if cur and p != cur[-1] + 1:
            groups.append(cur); cur = []
        cur.append(p)
    if cur: groups.append(cur)
    # Les vraies lignes de grille sont fines (< 12 px) : la plus proche de chaque tiers.
    thin = [g for g in groups if len(g) < 12]
    return [(g[0], g[-1]) for g in (min(thin, key=lambda g: abs((g[0] + g[-1]) / 2 - size * k / 3)) for k in (1, 2))]

cols = lines(full.mean(axis=0), W)
rows = lines(full.mean(axis=1), H)
xs = [0] + [v for c in cols for v in c] + [W]
ys = [0] + [v for r in rows for v in r] + [H]
cells_x = [(xs[i], xs[i + 1]) for i in range(0, len(xs), 2)]
cells_y = [(ys[i], ys[i + 1]) for i in range(0, len(ys), 2)]
assert len(cells_x) == 3 and len(cells_y) == 3, (cols, rows)
os.makedirs(f'{out}/{theme}', exist_ok=True)
for r, (y0, y1) in enumerate(cells_y):
    for c, (x0, x1) in enumerate(cells_x):
        cell = arr[y0 + 6:y1 - 6, x0 + 6:x1 - 6].copy()
        a = cell[..., 3] > 40
        lab, n = ndimage.label(a)
        cw, ch = cell.shape[1], cell.shape[0]
        for j, sl in enumerate(ndimage.find_objects(lab)):
            # Étiquette : petite pastille collée au coin haut gauche.
            if sl[0].stop < ch * .16 and sl[1].stop < cw * .32 and sl[0].start < 12:
                cell[sl][lab[sl] == j + 1, 3] = 0
        # Étiquette soudée à la pancarte : pastille de couleur unie qui part du coin haut gauche.
        # On suit sa couleur depuis le coin (zone limitée à 40 x 170 px), puis on efface son rectangle.
        zone = cell[:40, :170]
        seed = zone[12, 10]
        if seed[3] > 200:
            near = (np.abs(zone[..., :3].astype(int) - seed[:3].astype(int)).sum(-1) < 60) & (zone[..., 3] > 200)
            blob, _ = ndimage.label(near)
            if blob[12, 10]:
                ys, xs = np.where(blob == blob[12, 10])
                cell[:ys.max() + 3, :xs.max() + 3, 3] = 0
        Image.fromarray(cell).save(f'{out}/{theme}/{ORDER[r * 3 + c]}.png')
print('cases :', cells_x, cells_y)
