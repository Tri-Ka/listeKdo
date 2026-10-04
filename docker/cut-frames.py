# Découpe de la planche des cadres de photo img/skins-sources/cadres.png en img/frames/<clé>.webp.
# Lancer depuis docker/ (Pillow, numpy, scipy) :
#   docker run --rm -e HOME=/tmp --user "$(id -u):$(id -g)" -v "$PWD/..:/w" python:3.12-slim sh -c "pip -q install --user pillow numpy scipy && python /w/docker/cut-frames.py"
# Chaque cadre est recentré sur le trou de l'anneau, vidé à l'intérieur (pas de voile sur la photo), puis mis à
# l'échelle pour que le trou fasse toujours HOLE de la largeur de l'image : le CSS n'a qu'une seule mesure (« Cadres »).
from PIL import Image
import numpy as np
from scipy import ndimage
import os, sys

SRC = '/w/listeKdo/img/skins-sources/cadres.png'
OUT = '/w/listeKdo/img/frames'
SIZE = 360     # côté de l'image produite
HOLE = .46     # diamètre du trou / côté de l'image

CELLS = {  # clé -> cellule de la planche (x0, y0, x1, y1), dans l'ordre de la planche
    'fleurs': (0, 0, 330, 318), 'etoiles': (330, 0, 630, 318), 'champetre': (630, 0, 940, 318), 'cosmos': (940, 0, 1254, 318),
    'neon-retro': (0, 318, 330, 622), 'pirate': (330, 318, 635, 622), 'steampunk': (635, 318, 940, 622), 'farwest': (940, 318, 1254, 622),
    'lavande': (0, 622, 330, 930), 'zombie': (330, 622, 635, 930), 'romance': (635, 622, 930, 930), 'soleil-lune': (930, 622, 1254, 930),
    'royal': (280, 930, 632, 1254), 'diamant': (632, 930, 980, 1254),
}

im = np.array(Image.open(SRC).convert('RGBA'))
os.makedirs(OUT, exist_ok=True)
for key, (x0, y0, x1, y1) in CELLS.items():
    c = im[y0:y1, x0:x1].copy()
    a = c[..., 3] > 40
    # Plus grand morceau de la cellule (le cadre), avec ses éclats proches
    lab, n = ndimage.label(ndimage.binary_dilation(a, iterations=6))
    sizes = ndimage.sum(a, lab, range(1, n + 1))
    keep = lab == (int(np.argmax(sizes)) + 1)
    c[..., 3] = np.where(keep, c[..., 3], 0)
    # Trou : composante transparente qui contient le centre du cadre
    ys, xs = np.where(c[..., 3] > 40)
    cy, cx = (ys.min() + ys.max()) / 2, (xs.min() + xs.max()) / 2
    solid = ndimage.binary_closing(c[..., 3] > 140, iterations=2)
    holes, _ = ndimage.label(~solid)
    hole = holes == holes[int(cy), int(cx)]
    hy, hx = np.where(hole)
    cy, cx = hy.mean(), hx.mean()
    # Rayon du trou : bord intérieur de l'anneau, direction par direction (médiane) ; un ornement qui rentre dans
    # le trou (coffre du pirate, nuages…) reste par-dessus la photo.
    oy, ox = np.where(solid)
    dist = np.sqrt((oy - cy) ** 2 + (ox - cx) ** 2)
    ang = ((np.arctan2(oy - cy, ox - cx) + np.pi) / (2 * np.pi) * 72).astype(int) % 72
    inner = np.array([dist[ang == i].min() for i in range(72) if (ang == i).any()])
    r = float(np.median(inner))
    yy, xx = np.mgrid[0:c.shape[0], 0:c.shape[1]]
    d = np.sqrt((yy - cy) ** 2 + (xx - cx) ** 2)
    c[..., 3] = np.where(d < min(r - 2, inner.min() - 1), 0, c[..., 3])
    # Recentrage et mise à l'échelle : trou = HOLE * SIZE
    scale = HOLE * SIZE / (2 * r)
    big = int(round(SIZE / scale))
    canvas = np.zeros((big, big, 4), np.uint8)
    ox0, oy0 = int(round(big / 2 - cx)), int(round(big / 2 - cy))
    sy0, sx0 = max(0, -oy0), max(0, -ox0)
    sy1, sx1 = min(c.shape[0], big - oy0), min(c.shape[1], big - ox0)
    lost = int((c[..., 3] > 40).sum() - (c[sy0:sy1, sx0:sx1, 3] > 40).sum())
    canvas[sy0 + oy0:sy1 + oy0, sx0 + ox0:sx1 + ox0] = c[sy0:sy1, sx0:sx1]
    img = Image.fromarray(canvas).resize((SIZE, SIZE), Image.LANCZOS)
    img.save(OUT + '/' + key + '.webp', quality=86, method=6)
    print(key, 'trou %.0f px' % (2 * r), 'pixels coupés', lost, os.path.getsize(OUT + '/' + key + '.webp') // 1024, 'Ko')
