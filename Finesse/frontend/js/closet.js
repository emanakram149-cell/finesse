// CLOSET page logic
let CLOSET_STATE = { items: [], cats: [], cat: '', q: '' };

async function loadCategories() {
  const r = await F.get(F.api + '/items.php?action=categories');
  const categories = Array.isArray(r.categories) ? r.categories : [];
  CLOSET_STATE.cats = categories;
  F.$('#cat-pills').innerHTML =
    `<span class="pill ${CLOSET_STATE.cat===''?'active':''}" onclick="setCat('')">All</span>` +
    categories.map(c => `<span class="pill ${CLOSET_STATE.cat===c.slug?'active':''}" onclick="setCat('${c.slug}')">${c.name}</span>`).join('');

  const sel = F.$('#cat-select');
  if (sel) {
    sel.innerHTML = categories.length
      ? categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')
      : `<option value="">No categories found</option>`;
    sel.disabled = categories.length === 0;
  }
}
async function loadItems() {
  const u = new URL(F.api + '/items.php', location.href);
  u.searchParams.set('action','list');
  if (CLOSET_STATE.cat) u.searchParams.set('category', CLOSET_STATE.cat);
  if (CLOSET_STATE.q) u.searchParams.set('q', CLOSET_STATE.q);
  const r = await F.get(u.toString());
  CLOSET_STATE.items = r.items || [];
  renderItems();
}
function renderItems() {
  const grid = F.$('#item-grid');
  if (!CLOSET_STATE.items.length) {
    grid.innerHTML = `<div class="empty" style="grid-column:1/-1">
      <h3 style="margin-bottom:.5rem">Your closet awaits</h3>
      <p>Upload your first piece to begin curating signature looks.</p>
    </div>`;
    return;
  }
  grid.innerHTML = CLOSET_STATE.items.map(i => `
    <div class="item">
      <img src="${i.image}" alt="${i.name}" loading="lazy">
      <div class="ov"><b>${i.name}</b><span>${i.category}${i.color?' · '+i.color:''}</span></div>
      <button class="del" title="Delete" onclick="delItem(${i.id})">×</button>
    </div>`).join('');
}
function setCat(s) { CLOSET_STATE.cat = s; loadCategories(); loadItems(); }
async function delItem(id) {
  if (!confirm('Remove this piece?')) return;
  await F.post(F.api + '/items.php', { action:'delete', id });
  F.toast('Item removed'); loadItems();
}
window.setCat = setCat; window.delItem = delItem;

function openAdd() { F.$('#add-modal').classList.add('show'); }
function closeAdd() { F.$('#add-modal').classList.remove('show'); }
window.openAdd = openAdd; window.closeAdd = closeAdd;

async function submitAdd(e) {
  e.preventDefault();
  const fd = new FormData(e.target); fd.append('action','add');
  const r = await F.post(F.api + '/items.php', fd);
  if (r.ok) { F.toast('Piece added to closet'); closeAdd(); e.target.reset(); loadItems(); }
  else F.toast(r.msg || 'Upload failed', 'err');
}
window.submitAdd = submitAdd;

document.addEventListener('DOMContentLoaded', () => {
  loadCategories(); loadItems();
  F.$('#search').addEventListener('input', e => { CLOSET_STATE.q = e.target.value; clearTimeout(F._s); F._s = setTimeout(loadItems, 250); });
});
