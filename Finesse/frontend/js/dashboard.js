// DASHBOARD logic
async function loadDashboard() {
  const meR = await F.get(F.api + '/auth.php?action=me');
  if (!meR.ok) { location.href = 'login.html'; return; }
  F.$('#hello').textContent = `Welcome, ${meR.name.split(' ')[0]}`;

  const items = (await F.get(F.api + '/items.php?action=list')).items || [];
  const outfits = (await F.get(F.api + '/outfit-engine.php?action=list')).outfits || [];
  const planner = (await F.get(F.api + '/planner.php?action=list')).entries || [];

  F.$('#stat-items').textContent = items.length;
  F.$('#stat-outfits').textContent = outfits.length;
  F.$('#stat-planned').textContent = planner.length;
  F.$('#stat-streak').textContent = Math.min(items.length, 30);

  const recent = outfits.slice(0,5);
  F.$('#recent-outfits').innerHTML = recent.length
    ? recent.map(o => `<a class="outfit-row" href="diva.html?outfit=${encodeURIComponent(o.id)}" style="display:flex;gap:.8rem;align-items:center;padding:.55rem 0;border-bottom:1px solid var(--border)">
        <div class="thumb">✦</div>
        <div class="meta"><b>${o.name}</b><span>${new Date(o.created_at).toLocaleDateString()}</span></div>
      </a>`).join('')
    : `<p class="muted">No saved outfits yet — head to <a style="color:var(--accent)" href="diva.html">Diva Studio</a>.</p>`;

  // Weather (prefer live location, fallback to London)
  let w;
  try {
    const pos = await F.geo();
    const lat = pos.coords.latitude;
    const lon = pos.coords.longitude;
    w = await F.get(F.api + `/weather-api.php?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`);
  } catch (_) {
    w = await F.get(F.api + '/weather-api.php?city=London');
  }
  const cond = w.condition || (w.weather?.[0]?.main || 'mild').toLowerCase();
  F.$('#w-temp').textContent = Math.round(w.main?.temp ?? 22) + '°';
  F.$('#w-cond').textContent = w.weather?.[0]?.description || 'mild';
  F.$('#w-city').textContent = w.name || 'Your location';

  // AI suggestion
  const rec = await F.get(F.api + `/outfit-engine.php?action=recommend&weather=${cond}&temp=${w.main?.temp ?? 22}`);
  const picks = rec.picks || [];
  F.$('#ai-picks').innerHTML = picks.length
    ? picks.map(p => `<div style="text-align:center"><div style="aspect-ratio:1;background:var(--bone);border-radius:12px;overflow:hidden;border:1px solid var(--border)"><img src="${p.image}" style="width:100%;height:100%;object-fit:cover"></div><small style="display:block;margin-top:.4rem;color:var(--muted)">${p.name}</small></div>`).join('')
    : `<p class="muted" style="grid-column:1/-1">Add pieces to your closet to receive AI styling.</p>`;
}
document.addEventListener('DOMContentLoaded', loadDashboard);
