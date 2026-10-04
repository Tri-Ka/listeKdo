const { chromium } = require('playwright-core');
const items = [['casque','🎧','#e0f2ff','#b8dcff'],['livre','📚','#fff1d6','#ffd89a'],['velo','🚲','#e3fbe9','#b5efc6'],['lego','🏰','#f1e8ff','#d6c2ff'],['plaid','🧶','#ffe6ea','#ffc2cc'],['the','🫖','#fff6dc','#ffe3a1'],['doudou','🧸','#fdeee0','#f6cfa8'],['jeu','🎲','#e6f7f6','#b4e7e3'],['plante','🪴','#eaf7e4','#c6e8b5'],['photo','📷','#fff0e0','#ffd2a6']];
(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 440, height: 330 } });
  for (const [n, e, c1, c2] of items) {
    await p.setContent(`<body style="margin:0;display:grid;place-items:center;height:330px;background:radial-gradient(circle at 50% 40%, ${c1}, ${c2})"><div style="font-size:160px;line-height:1;filter:drop-shadow(0 10px 14px rgba(0,0,0,.18))">${e}</div></body>`);
    await p.screenshot({ path: `out/demo-${n}.png` });
  }
  await b.close();
})();
