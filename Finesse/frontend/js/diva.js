// DIVA STUDIO — drag & drop outfit builder
const DIVA = { items: [], composed: { top:null, bottom:null, shoes:null, acc:null, full:null }, rotation: 0 };

async function loadPool() {
  const r = await F.get(F.api + '/items.php?action=list');
  DIVA.items = r.items || [];
  F.$('#diva-pool').innerHTML = DIVA.items.map(i => `
    <div class="pool-item" draggable="true" data-id="${i.id}" data-cat="${i.slug}" title="${i.name}">
      <img src="${i.image}" alt="${i.name}">
    </div>`).join('') || '<p class="muted" style="grid-column:1/-1;padding:1rem">Closet is empty. Upload pieces first.</p>';

  F.$$('.pool-item').forEach(el => {
    el.addEventListener('dragstart', e => {
      e.dataTransfer.setData('text/plain', el.dataset.id);
      e.dataTransfer.setData('cat', el.dataset.cat);
    });
  });
}
function placeItemInSlot(item) {
  const map = { tops:'top', bottoms:'bottom', dresses:'full', jumpsuits:'full', sets:'full', outerwear:'top', shoes:'shoes', accessories:'acc', jewelry:'acc', handbags:'acc' };
  const target = map[item.slug];
  if (!target) return;
  if (target === 'full') {
    DIVA.composed.top = null;
    DIVA.composed.bottom = null;
    const top = F.$('.slot.top');
    const bottom = F.$('.slot.bottom');
    if (top) top.innerHTML = top.dataset.label;
    if (bottom) bottom.innerHTML = bottom.dataset.label;
  } else if (target === 'top' || target === 'bottom') {
    DIVA.composed.full = null;
    const full = F.$('.slot.full');
    if (full) full.innerHTML = '';
  }
  DIVA.composed[target] = item;
  const slot = F.$(`.slot.${target}`);
  if (slot) slot.innerHTML = `<img src="${item.image}" alt="${item.name}">`;
}
function setupSlots() {
  const map = { tops:'top', bottoms:'bottom', dresses:'full', jumpsuits:'full', sets:'full', outerwear:'top', shoes:'shoes', accessories:'acc', jewelry:'acc', handbags:'acc' };
  F.$$('.slot').forEach(slot => {
    slot.addEventListener('dragover', e => { e.preventDefault(); slot.classList.add('over'); });
    slot.addEventListener('dragleave', () => slot.classList.remove('over'));
    slot.addEventListener('drop', e => {
      e.preventDefault(); slot.classList.remove('over');
      const id = e.dataTransfer.getData('text/plain');
      const cat = e.dataTransfer.getData('cat');
      const item = DIVA.items.find(i => i.id == id); if (!item) return;
      const target = map[cat] || slot.dataset.slot;
      placeItemInSlot(item);
    });
  });
}
function rotateMannequin(dir) {
  DIVA.rotation += dir * 25;
  F.$('.mannequin').style.transform = `rotateY(${DIVA.rotation}deg)`;
}
window.rotateMannequin = rotateMannequin;

async function saveOutfit() {
  const ids = Object.values(DIVA.composed).filter(Boolean).map(i => i.id);
  if (!ids.length) return F.toast('Compose a look first', 'err');
  const name = prompt('Name your look:', 'Untitled Look') || 'Untitled Look';
  const r = await F.post(F.api + '/outfit-engine.php?action=save', { name, item_ids: JSON.stringify(ids) });
  if (r.ok) F.toast('Look saved to your archive');
}
function clearLook() {
  DIVA.composed = { top:null, bottom:null, shoes:null, acc:null, full:null };
  F.$$('.slot').forEach(s => s.innerHTML = s.dataset.label);
}
async function autoCompose() {
  const r = await F.get(F.api + '/outfit-engine.php?action=recommend');
  clearLook();
  (r.picks || []).forEach(p => {
    placeItemInSlot(p);
  });
}
async function loadOutfitFromQuery() {
  const outfitId = new URLSearchParams(location.search).get('outfit');
  if (!outfitId) return;
  const r = await F.get(F.api + '/outfit-engine.php?action=list');
  if (!r.ok) return;
  const outfit = (r.outfits || []).find(o => String(o.id) === String(outfitId));
  if (!outfit) return;
  let ids = [];
  try { ids = JSON.parse(outfit.item_ids || '[]'); } catch (_) { ids = []; }
  if (!Array.isArray(ids) || !ids.length) return;
  clearLook();
  ids.forEach(id => {
    const item = DIVA.items.find(i => String(i.id) === String(id));
    if (item) placeItemInSlot(item);
  });
  F.toast(`Loaded look: ${outfit.name}`);
}
window.saveOutfit = saveOutfit; window.clearLook = clearLook; window.autoCompose = autoCompose;

document.addEventListener('DOMContentLoaded', async () => {
  setupSlots();
  await loadPool();
  await loadOutfitFromQuery();
});
