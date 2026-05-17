// Auth pages
function authApiBase() {
  if (typeof F !== 'undefined' && F.api) return F.api;
  const p = location.pathname;
  const i = p.indexOf('/frontend/');
  if (i >= 0) return p.slice(0, i) + '/backend';
  return '/backend';
}

async function refreshCaptcha() {
  const q = document.getElementById('captcha-q');
  const row = document.getElementById('captcha-row');
  const ans = document.getElementById('captcha-a');
  const rec = document.getElementById('recaptcha');
  if (!q || !row || !rec) return;

  const showMath = (data) => {
    rec.style.display = 'none';
    rec.innerHTML = '';
    row.style.display = 'flex';
    if (ans) {
      ans.required = true;
      ans.value = '';
    }
    q.textContent = data?.question || '—';
  };

  const base = authApiBase();

  try {
    const r = await fetch(base + '/auth.php?action=captcha&force=math', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    const data = await r.json();
    if (data?.ok && data.type === 'math') {
      showMath(data);
      return;
    }
    q.textContent = 'Tap ↻ to reload';
  } catch (_) {
    q.textContent = 'Tap ↻ to reload';
  }
}

function scorePassword(pw) {
  const s = String(pw || '');
  let score = 0;
  if (s.length >= 6) score += 1;
  if (s.length >= 10) score += 1;
  if (/[a-z]/.test(s) && /[A-Z]/.test(s)) score += 1;
  if (/\d/.test(s)) score += 1;
  if (/[^A-Za-z0-9]/.test(s)) score += 1;
  return Math.min(score, 5);
}
function renderPwStrength() {
  const inp = document.getElementById('pw');
  const bar = document.getElementById('pw-bar');
  const lbl = document.getElementById('pw-label');
  if (!inp || !bar || !lbl) return;
  const sc = scorePassword(inp.value);
  const pct = (sc / 5) * 100;
  bar.style.width = pct + '%';
  const text = sc <= 2 ? 'Fair' : sc <= 4 ? 'Strong' : 'Excellent';
  lbl.textContent = inp.value ? `Strength: ${text}` : 'Strength: —';
  bar.dataset.level = String(sc);
}

async function doSignup(e) {
  e.preventDefault();
  const f = e.target;
  const fd = new FormData(f);
  fd.append('action', 'signup');
  const r = await F.post(authApiBase() + '/auth.php', fd);
  const m = F.$('.msg', f);
  if (r.ok) {
    m.className = 'msg ok';
    m.textContent = 'Welcome to Laleh — entering studio…';
    setTimeout(() => { location.href = r.redirect; }, 800);
  } else {
    m.className = 'msg err';
    m.textContent = r.msg || 'Signup failed';
    refreshCaptcha();
  }
}
async function doLogin(e) {
  e.preventDefault();
  const f = e.target;
  const fd = new FormData(f);
  fd.append('action', 'login');
  const r = await F.post(authApiBase() + '/auth.php', fd);
  const m = F.$('.msg', f);
  if (r.ok) {
    m.className = 'msg ok';
    m.textContent = 'Signed in. Welcome back.';
    setTimeout(() => { location.href = r.redirect; }, 600);
  } else {
    m.className = 'msg err';
    m.textContent = r.msg || 'Login failed';
    refreshCaptcha();
  }
}
window.doSignup = doSignup;
window.doLogin = doLogin;
window.refreshCaptcha = refreshCaptcha;

document.addEventListener('DOMContentLoaded', () => {
  refreshCaptcha();
  const pw = document.getElementById('pw');
  if (pw) {
    pw.addEventListener('input', renderPwStrength);
    renderPwStrength();
  }
});
