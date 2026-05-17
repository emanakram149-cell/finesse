// PLANNER — calendar + weather + outfit assignment
const PL = { date: new Date(), entries: [], outfits: [] };
function updateMap(lat, lon) {
  const frame = F.$('#planner-map');
  if (!frame || !Number.isFinite(lat) || !Number.isFinite(lon)) return;
  frame.src = `https://maps.google.com/maps?q=${encodeURIComponent(`${lat},${lon}`)}&z=12&output=embed`;
}
function updateMapByCity(city) {
  const frame = F.$('#planner-map');
  if (!frame || !city) return;
  frame.src = `https://maps.google.com/maps?q=${encodeURIComponent(city)}&z=11&output=embed`;
}

function buildCalendar() {
  const d = PL.date;
  const y = d.getFullYear(), m = d.getMonth();
  const first = new Date(y, m, 1);
  const last = new Date(y, m+1, 0);
  const startDay = first.getDay();
  const days = last.getDate();
  const today = new Date(); const todayKey = today.toISOString().slice(0,10);
  const has = new Set(PL.entries.map(e => e.date));

  F.$('#cal-month').textContent = d.toLocaleString('en-US',{month:'long', year:'numeric'});
  const grid = F.$('#cal-grid');
  const dows = ['S','M','T','W','T','F','S'];
  grid.innerHTML = dows.map(d => `<div class="cal-day">${d}</div>`).join('');
  for (let i=0; i<startDay; i++) grid.insertAdjacentHTML('beforeend','<div></div>');
  for (let i=1; i<=days; i++) {
    const key = `${y}-${String(m+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
    const cls = ['cal-cell'];
    if (key === todayKey) cls.push('today');
    if (has.has(key)) cls.push('has');
    grid.insertAdjacentHTML('beforeend', `<div class="${cls.join(' ')}" onclick="openPlan('${key}')">${i}</div>`);
  }
}
function shiftMonth(n) { PL.date.setMonth(PL.date.getMonth()+n); buildCalendar(); }
window.shiftMonth = shiftMonth;

async function loadAll() {
  PL.entries = (await F.get(F.api + '/planner.php?action=list')).entries || [];
  PL.outfits = (await F.get(F.api + '/outfit-engine.php?action=list')).outfits || [];
  buildCalendar();
  renderEntries();
  useCurrentLocationWeather();
}
function renderEntries() {
  F.$('#entries').innerHTML = PL.entries.length
    ? PL.entries.slice(0,8).map(e => `<div class="outfit-row">
        <div class="thumb">▣</div>
        <div class="meta"><b>${e.outfit_name || 'Custom plan'}</b><span>${e.date}${e.note?' · '+e.note:''}</span></div>
      </div>`).join('')
    : `<p class="muted">No plans yet — click any date.</p>`;
}
function openPlan(date) {
  F.$('#plan-date').value = date;
  const sel = F.$('#plan-outfit');
  sel.innerHTML = '<option value="">— No outfit —</option>' + PL.outfits.map(o => `<option value="${o.id}">${o.name}</option>`).join('');
  F.$('#plan-modal').classList.add('show');
}
function closePlan() { F.$('#plan-modal').classList.remove('show'); }
window.openPlan = openPlan; window.closePlan = closePlan;

async function submitPlan(e) {
  e.preventDefault();
  const fd = new FormData(e.target); fd.append('action','add');
  await F.post(F.api + '/planner.php', fd);
  F.toast('Look planned'); closePlan(); loadAll();
}
window.submitPlan = submitPlan;

async function loadWeather() {
  const city = F.$('#city-input')?.value || 'Sargodha';
  const w = await F.get(F.api + '/weather-api.php?city=' + encodeURIComponent(city));
  updateMapByCity(city);
  await renderWeather(w, city);
}
async function renderWeather(w, fallbackCity = 'Sargodha') {
  const cond = w.condition || (w.weather?.[0]?.main || 'mild').toLowerCase();
  F.$('#w-temp').textContent = Math.round(w.main?.temp ?? 22) + '°';
  F.$('#w-cond').textContent = w.weather?.[0]?.description || cond;
  F.$('#w-city').textContent = w.name || fallbackCity;

  // Suggest
  const rec = await F.get(F.api + `/outfit-engine.php?action=recommend&weather=${cond}&temp=${w.main?.temp ?? 22}`);
  F.$('#suggest').innerHTML = (rec.picks || []).length
    ? rec.picks.map(p => `<div style="aspect-ratio:1;border-radius:12px;overflow:hidden;border:1px solid var(--border)"><img src="${p.image}" style="width:100%;height:100%;object-fit:cover"></div>`).join('')
    : '<p class="muted" style="grid-column:1/-1">Add closet pieces for live suggestions.</p>';
}
window.loadWeather = loadWeather;
async function useCurrentLocationWeather() {
  try {
    const pos = await F.geo();
    const lat = pos.coords.latitude;
    const lon = pos.coords.longitude;
    updateMap(lat, lon);
    const w = await F.get(F.api + `/weather-api.php?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`);
    await renderWeather(w, 'Your location');
    const cityInput = F.$('#city-input');
    if (cityInput && w.name) cityInput.value = w.name;
  } catch (err) {
    const code = err && typeof err.code === 'number' ? err.code : -1;
    if (code === 1) F.toast('Location permission denied. Allow location in browser settings.', 'err');
    else if (code === 2) F.toast('Location unavailable. Check device location services.', 'err');
    else if (code === 3) F.toast('Location request timed out. Try again.', 'err');
    else F.toast('Could not get live location. Try again.', 'err');
  }
}
window.useCurrentLocationWeather = useCurrentLocationWeather;

async function randomOutfit() {
  const rec = await F.get(F.api + '/outfit-engine.php?action=recommend');
  F.$('#suggest').innerHTML = (rec.picks || []).map(p => `<div style="aspect-ratio:1;border-radius:12px;overflow:hidden;border:1px solid var(--border)"><img src="${p.image}" style="width:100%;height:100%;object-fit:cover"></div>`).join('') || '<p class="muted">Closet empty.</p>';
  F.toast('New random look generated');
}
window.randomOutfit = randomOutfit;

document.addEventListener('DOMContentLoaded', loadAll);
