/**
 * LALEH — Shared JS  v3
 * (theme · loader · scroll-reveal · datetime · accessibility · AI chatbot · global search · FABs)
 *
 * NEW in v3:
 *  - F.search — sidebar search bar with live dropdown (items, outfits, plans)
 *  - Ctrl+K / ⌘+K keyboard shortcut opens full-screen search overlay
 *  - Search highlights matched text in results
 *  - All v2 chatbot features retained
 */

/* ── Core namespace ──────────────────────────────────────────── */
const resolveApiBase = () => {
  const p = location.pathname;
  const i = p.indexOf('/frontend/');
  if (i >= 0) return p.slice(0, i) + '/backend';
  return '/backend';
};

const F = {
  api: resolveApiBase(),
  $:  (s, r = document) => r.querySelector(s),
  $$: (s, r = document) => [...r.querySelectorAll(s)],
  geo: () => new Promise((resolve, reject) => {
    if (!navigator.geolocation) return reject(new Error('Geolocation not supported'));
    navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 });
  }),
};
window.F = F;

/* ── Cookies consent ─────────────────────────────────────────── */
const initCookiesConsent = () => {
  const key = 'laleh-cookies-consent';
  if (localStorage.getItem(key)) return;

  const el = document.createElement('div');
  el.id = '__cookies';
  el.innerHTML = `
    <div class="c-card">
      <div class="c-title">Cookies</div>
      <div class="c-text">We use cookies to remember theme and keep you signed in. You can accept or decline non-essential cookies.</div>
      <div class="c-actions">
        <button class="btn btn-ghost" data-act="decline" type="button">Decline</button>
        <button class="btn btn-primary" data-act="accept" type="button">Accept</button>
      </div>
    </div>`;
  document.body.appendChild(el);

  el.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-act]');
    if (!b) return;
    const act = b.getAttribute('data-act');
    localStorage.setItem(key, act === 'accept' ? 'accepted' : 'declined');
    el.classList.add('hide');
    setTimeout(() => el.remove(), 250);
  });
};

/* ── Page transitions ─────────────────────────────────────────── */
const initPageTransitions = () => {
  const layer = document.createElement('div');
  layer.id = '__page-transition';
  document.body.appendChild(layer);
  requestAnimationFrame(() => layer.classList.add('hide'));

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;

    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin) return;

    const isFrontendHtml = /\/frontend\/.*\.html$/i.test(url.pathname);
    if (!isFrontendHtml) return;
    if (url.pathname === location.pathname && url.search === location.search) return;

    e.preventDefault();
    layer.classList.remove('hide');
    layer.classList.add('show');
    setTimeout(() => { location.href = url.href; }, 220);
  });
};

/* ── Theme ───────────────────────────────────────────────────── */
const initTheme = () => {
  const saved = localStorage.getItem('laleh-theme') || localStorage.getItem('finesse-theme');
  if (saved === 'dark')
    document.documentElement.classList.add('dark');
};
const toggleTheme = () => {
  const dark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('laleh-theme', dark ? 'dark' : 'light');
};
window.toggleTheme = toggleTheme;
initTheme();
document.addEventListener('DOMContentLoaded', initPageTransitions);
document.addEventListener('DOMContentLoaded', initCookiesConsent);

/* ── Page loader ─────────────────────────────────────────────── */
window.addEventListener('load', () => {
  setTimeout(() => F.$('.loader')?.classList.add('hide'), 500);
});

/* ── Reveal on scroll ────────────────────────────────────────── */
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  });
}, { threshold: 0.12 });

document.addEventListener('DOMContentLoaded', () => {
  F.$$('.reveal').forEach(el => io.observe(el));
});

/* ── DateTime widget ─────────────────────────────────────────── */
const tickDT = () => {
  const el = F.$('#dt-widget');
  if (!el) return;
  const d = new Date();
  el.innerHTML =
    `<b>${d.toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' })}</b>` +
    ` · ${d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' })}`;
};
setInterval(tickDT, 1000);
document.addEventListener('DOMContentLoaded', tickDT);

/* ── Accessibility toolbar ───────────────────────────────────── */
window.a11y = {
  contrast() { document.documentElement.classList.toggle('contrast'); },
  bigger() {
    const cur = parseFloat(getComputedStyle(document.documentElement).fontSize);
    document.documentElement.style.fontSize = (cur + 2) + 'px';
  },
  smaller() {
    const cur = parseFloat(getComputedStyle(document.documentElement).fontSize);
    document.documentElement.style.fontSize = (cur - 2) + 'px';
  },
  speak() {
    if (!('speechSynthesis' in window)) return alert('TTS not supported');
    speechSynthesis.cancel();
    speechSynthesis.speak(new SpeechSynthesisUtterance(document.body.innerText.slice(0, 1500)));
  },
  stop() { speechSynthesis?.cancel(); },
};

/* ================================================================
   GLOBAL SEARCH  — sidebar bar + Ctrl+K overlay
   ================================================================ */
F.search = {

  _timer:     null,
  _lastQuery: '',
  _open:      false,

  init() {
    const input = F.$('#sidebar-search-input');
    const clear = F.$('#sidebar-search-clear');
    if (!input) return;

    input.addEventListener('input', () => {
      const q = input.value.trim();
      clear && (clear.style.display = q ? 'block' : 'none');
      clearTimeout(F.search._timer);
      if (q.length < 2) { F.search.hideDropdown(); return; }
      F.search.showDropdown('<div class="s-loading">Searching</div>');
      F.search._timer = setTimeout(() => F.search.fetch(q), 280);
    });

    clear && clear.addEventListener('click', () => {
      input.value = '';
      clear.style.display = 'none';
      F.search.hideDropdown();
      input.focus();
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') { F.search.hideDropdown(); input.blur(); }
    });

    document.addEventListener('click', e => {
      if (!F.$('.sidebar-search')?.contains(e.target)) F.search.hideDropdown();
    });

    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault(); F.search.openOverlay();
      }
    });

    const overlay = F.$('#global-search-overlay');
    overlay && overlay.addEventListener('click', e => {
      if (e.target === overlay) F.search.closeOverlay();
    });

    const overlayInput = F.$('#global-search-input');
    overlayInput && overlayInput.addEventListener('input', () => {
      const q = overlayInput.value.trim();
      clearTimeout(F.search._timer);
      const box = F.$('#global-search-results');
      if (q.length < 2) { box.innerHTML = '<div class="s-empty">Type to search…</div>'; return; }
      box.innerHTML = '<div class="s-loading">Searching</div>';
      F.search._timer = setTimeout(() => F.search.fetchOverlay(q), 280);
    });

    overlayInput && overlayInput.addEventListener('keydown', e => {
      if (e.key === 'Escape') F.search.closeOverlay();
    });
  },

  async fetch(q) {
    if (q === F.search._lastQuery) return;
    F.search._lastQuery = q;
    try {
      const data = await F.get(F.api + '/search.php?q=' + encodeURIComponent(q));
      if (!data.ok) { F.search.showDropdown('<div class="s-empty">Login to search.</div>'); return; }
      F.search.showDropdown(F.search.buildHTML(data.results, q, true));
    } catch (_) {
      F.search.showDropdown('<div class="s-empty">Search unavailable.</div>');
    }
  },

  async fetchOverlay(q) {
    const box = F.$('#global-search-results');
    try {
      const data = await F.get(F.api + '/search.php?q=' + encodeURIComponent(q));
      box.innerHTML = data.results.length
        ? F.search.buildHTML(data.results, q, false)
        : `<div class="s-empty">No results for "<b>${F.search._esc(q)}</b>"</div>`;
    } catch (_) {
      box.innerHTML = '<div class="s-empty">Search unavailable.</div>';
    }
  },

  buildHTML(results, q, compact) {
    if (!results.length)
      return `<div class="s-empty">No results for "<b>${F.search._esc(q)}</b>"</div>`;

    const groups = { item: [], outfit: [], plan: [] };
    results.forEach(r => groups[r.type] && groups[r.type].push(r));
    const labels = { item: '❖ Closet Items', outfit: '✦ Saved Looks', plan: '▣ Planner' };
    let html = '';

    for (const [type, rows] of Object.entries(groups)) {
      if (!rows.length) continue;
      html += `<div class="s-section-label">${labels[type]}</div>`;
      rows.forEach(r => {
        const thumb = r.image
          ? `<div class="s-thumb"><img src="${r.image}" alt="" loading="lazy"></div>`
          : `<div class="s-thumb">${r.icon}</div>`;
        html += `
          <a href="${r.url}" class="s-result" tabindex="0">
            ${thumb}
            <div class="s-result-text">
              <b>${F.search._highlight(F.search._esc(r.title), q)}</b>
              <span>${F.search._esc(r.sub)}</span>
            </div>
            <span class="s-arrow">›</span>
          </a>`;
      });
    }

    if (compact) html += `<div class="s-shortcut">Press <kbd>Ctrl+K</kbd> for full search</div>`;
    return html;
  },

  showDropdown(html) {
    let dd = F.$('#sidebar-search-dropdown');
    if (!dd) {
      dd = document.createElement('div');
      dd.id = 'sidebar-search-dropdown';
      dd.className = 'search-dropdown';
      F.$('.sidebar-search') && F.$('.sidebar-search').appendChild(dd);
    }
    dd.innerHTML = html;
    dd.classList.add('open');
    F.search._open = true;
  },

  hideDropdown() {
    F.$('#sidebar-search-dropdown')?.classList.remove('open');
    F.search._open = false;
    F.search._lastQuery = '';
  },

  openOverlay() {
    F.$('#global-search-overlay')?.classList.add('show');
    setTimeout(() => F.$('#global-search-input')?.focus(), 80);
  },

  closeOverlay() {
    F.$('#global-search-overlay')?.classList.remove('show');
    const inp = F.$('#global-search-input');
    const box = F.$('#global-search-results');
    if (inp) inp.value = '';
    if (box) box.innerHTML = '<div class="s-empty">Type to search your wardrobe…</div>';
  },

  _esc(str = '') {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  },

  _highlight(escaped, q) {
    if (!q) return escaped;
    const safe = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escaped.replace(new RegExp(`(${safe})`, 'gi'),
      '<span class="s-highlight">$1</span>');
  },
};

/* ================================================================
   CHATBOT  — rule-based stylist via backend/chatbot.php
   ================================================================ */
window.chat = {

  toggle() {
    const win = F.$('.chat-window');
    win.classList.toggle('open');
    if (win.classList.contains('open'))
      setTimeout(() => F.$('#chat-input')?.focus(), 80);
  },

  async send(e) {
    e?.preventDefault();
    const inp  = F.$('#chat-input');
    const msg  = inp.value.trim();
    if (!msg || chat._busy) return;

    const body = F.$('.chat-body');
    body.insertAdjacentHTML('beforeend',
      `<div class="bubble me">${chat._escape(msg)}</div>`);
    inp.value = '';
    chat._scrollBottom(body);

    chat._busy = true;
    const typingId = `typing-${Date.now()}`;
    body.insertAdjacentHTML('beforeend',
      `<div class="bubble bot typing" id="${typingId}">
         <span class="dots"><span>·</span><span>·</span><span>·</span></span>
       </div>`);
    chat._scrollBottom(body);
    chat._injectTypingCSS();

    try {
      const fd = new FormData(); fd.append('message', msg);
      const res  = await fetch(F.api + '/chatbot.php',
        { method:'POST', body:fd, credentials:'same-origin' });
      const data = await res.json();
      document.getElementById(typingId)?.remove();
      const reply = data.reply || 'I\'m here whenever you need styling advice. ✨';
      body.insertAdjacentHTML('beforeend',
        `<div class="bubble bot">${chat._escape(reply).replace(/\n/g,'<br>')}</div>`);
    } catch (_) {
      document.getElementById(typingId)?.remove();
      body.insertAdjacentHTML('beforeend',
        '<div class="bubble bot">Connection issue — please try again. 🙏</div>');
    }

    chat._busy = false;
    chat._scrollBottom(body);
  },

  async clearHistory() {
    const body = F.$('.chat-body');
    body.innerHTML = '<div class="bubble bot">Cleared. How can I style you today? ✨</div>';
    try {
      const fd = new FormData(); fd.append('clear','1');
      await fetch(F.api + '/chatbot.php',{ method:'POST', body:fd, credentials:'same-origin' });
    } catch (_) {}
  },

  _busy: false,
  _scrollBottom(el) { el.scrollTop = el.scrollHeight; },
  _escape(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;')
              .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  },
  _cssInjected: false,
  _injectTypingCSS() {
    if (chat._cssInjected) return; chat._cssInjected = true;
    const s = document.createElement('style');
    s.textContent = `.bubble.typing{min-width:48px}.dots{display:inline-flex;gap:4px;
      align-items:center;height:20px}.dots span{display:inline-block;width:6px;height:6px;
      border-radius:50%;background:currentColor;opacity:.4;
      animation:dotBounce 1.2s ease-in-out infinite}
      .dots span:nth-child(2){animation-delay:.2s}.dots span:nth-child(3){animation-delay:.4s}
      @keyframes dotBounce{0%,80%,100%{transform:translateY(0);opacity:.4}
      40%{transform:translateY(-6px);opacity:1}}`;
    document.head.appendChild(s);
  },
};

/* ── Smooth scroll helpers ───────────────────────────────────── */
window.scrollUp   = () => window.scrollTo({ top:0, behavior:'smooth' });
window.scrollDown = () => window.scrollTo({ top:document.body.scrollHeight, behavior:'smooth' });

/* ================================================================
   HELPERS  —  F.toast  F.post  F.get
   ================================================================ */
F.toast = (msg, type = 'ok') => {
  let el = F.$('#__toast');
  if (!el) {
    el = document.createElement('div'); el.id = '__toast';
    el.style.cssText = 'position:fixed;bottom:6.5rem;left:50%;transform:translateX(-50%);' +
      'background:#1a1a1a;color:#f7f3ec;padding:.8rem 1.4rem;border-radius:999px;z-index:300;' +
      'font-size:.85rem;letter-spacing:.1em;box-shadow:0 14px 30px -10px rgba(0,0,0,.4);' +
      'transition:opacity .3s;pointer-events:none;';
    document.body.appendChild(el);
  }
  el.style.background = type === 'err' ? '#7a1f1f' : '#1a1a1a';
  el.textContent = msg; el.style.opacity = '1';
  clearTimeout(F._tt); F._tt = setTimeout(() => el.style.opacity = '0', 2400);
};

F.post = async (url, data) => {
  const fd = data instanceof FormData ? data : new FormData();
  if (!(data instanceof FormData)) Object.entries(data||{}).forEach(([k,v]) => fd.append(k,v));
  const r = await fetch(url, { method:'POST', body:fd, credentials:'same-origin' });
  return r.json();
};
F.get = async (url) => {
  const r = await fetch(url, { credentials:'same-origin' });
  return r.json();
};

/* ================================================================
   CHROME RENDERER  —  sidebar (with search bar) + floating UI
   ================================================================ */
F.renderChrome = (active = 'dashboard') => {
  const items = [
    ['dashboard', 'Dashboard',  '◇'],
    ['closet',    'Closet',     '❖'],
    ['diva',      'Diva Studio','✦'],
    ['planner',   'Planner',    '▣'],
    ['about',     'About',      '◌'],
    ['contactus', 'Contact Us', '✉'],
  ];

  return `
  <aside class="sidebar">
    <a class="logo" href="about.html">LA<span>LEH</span></a>

    <!-- ══ GLOBAL SEARCH BAR ═════════════════════════════ -->
    <div class="sidebar-search">
      <div class="sidebar-search-bar">
        <span class="s-icon" style="color:var(--muted);font-size:1rem">⌕</span>
        <input
          id="sidebar-search-input"
          type="search"
          placeholder="Search closet, looks…"
          autocomplete="off"
          maxlength="80"
          aria-label="Search wardrobe"
          style="flex:1;background:transparent;border:0;outline:none;
                 font-size:.82rem;color:var(--text);min-width:0"
        >
        <button
          id="sidebar-search-clear"
          title="Clear"
          aria-label="Clear search"
          style="display:none;background:transparent;border:0;color:var(--muted);
                 cursor:pointer;font-size:1rem;line-height:1;padding:0;flex-shrink:0"
        >×</button>
      </div>
    </div>
    <!-- ═════════════════════════════════════════════════ -->

    <nav>
      ${items.map(([slug,label,ic]) =>
        `<a href="${slug}.html" class="${slug===active?'active':''}">
          <span>${ic}</span> ${label}
        </a>`).join('')}
    </nav>

    <div class="foot">
      <button type="button" class="btn btn-ghost" style="width:100%;justify-content:center;gap:.5rem"
              onclick="toggleTheme()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 3a6 6 0 1 0 0 18 6 6 0 0 0 0-18Zm0 0v4M12 21v-4M3 12h4M17 12h4"/></svg>
        Theme
      </button>
      <a class="btn btn-ghost"
         style="width:100%;justify-content:center;margin-top:.5rem"
         href="${F.api}/auth.php?action=logout">Sign out</a>
      <div style="margin-top:.75rem;text-align:center;font-size:.68rem;
                  color:var(--muted);letter-spacing:.1em">
        <kbd style="background:var(--border);padding:1px 5px;
             border-radius:4px;font-size:.65rem">Ctrl+K</kbd> full search
      </div>
    </div>
  </aside>`;
};

F.renderFloating = () => `
<button class="wa-fab" title="WhatsApp"
        onclick="window.open('https://wa.me/000000000','_blank')">
  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16.6 14.2c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8 1-.1.2-.3.2-.5.1-1.5-.7-2.6-1.7-3.3-3.3-.1-.2 0-.4.1-.5.2-.1.3-.3.5-.5.1-.1.2-.3.3-.4.1-.2.1-.3 0-.5s-.6-1.4-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.4-.2.3-1 1-1 2.4s1 2.8 1.1 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.4-.6 1.6-1.2.2-.6.2-1.1.1-1.2-.1-.1-.3-.2-.5-.3Z"/>
    <path d="M20 12a8 8 0 0 1-11.9 7l-4.1 1 1.1-4A8 8 0 1 1 20 12Z"/>
  </svg>
</button>

<button class="chat-fab" title="Laleh AI Stylist" onclick="chat.toggle()">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
    <path d="M8 10h8M8 14h5"/>
  </svg>
</button>

<div class="scroll-fabs" aria-label="Page navigation">
  <button class="scroll-fab" title="Top" onclick="scrollUp()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="m18 15-6-6-6 6"/>
    </svg>
  </button>
  <button class="scroll-fab" title="Bottom" onclick="scrollDown()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="m6 9 6 6 6-6"/>
    </svg>
  </button>
</div>

<div class="chat-window">
  <div class="chat-head">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <div>
        <b>Laleh Stylist</b>
        <div style="font-size:.72rem;opacity:.7;letter-spacing:.15em;
                    text-transform:uppercase;margin-top:2px">AI · Powered by Claude</div>
      </div>
      <button onclick="chat.clearHistory()" title="Clear conversation"
              style="opacity:.5;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;
                     color:inherit;padding:.2rem .45rem;border-radius:5px;
                     border:1px solid rgba(255,255,255,.2)">Clear</button>
    </div>
  </div>
  <div class="chat-body">
    <div class="bubble bot">Welcome to Laleh ✨ How can I style you today?</div>
  </div>
  <form class="chat-input" onsubmit="chat.send(event)">
    <input id="chat-input" placeholder="Ask your stylist…"
           autocomplete="off" maxlength="500">
    <button type="submit">Send</button>
  </form>
</div>

<div class="a11y-bar" aria-label="Accessibility toolbar">
  <button type="button" title="High contrast" onclick="a11y.contrast()" aria-label="High contrast">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/></svg>
  </button>
  <button type="button" title="Increase text" onclick="a11y.bigger()" aria-label="Increase text size">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 7V4h3M4 17v3h3M20 7V4h-3M20 17v3h-3"/><path d="M9 12h6M12 9v6"/></svg>
  </button>
  <button type="button" title="Decrease text" onclick="a11y.smaller()" aria-label="Decrease text size">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 7V4h3M4 17v3h3M20 7V4h-3M20 17v3h-3"/><path d="M9 12h6"/></svg>
  </button>
  <button type="button" title="Read page" onclick="a11y.speak()" aria-label="Read page aloud">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a9 9 0 0 1 0 14"/></svg>
  </button>
  <button type="button" title="Stop reading" onclick="a11y.stop()" aria-label="Stop reading">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
  </button>
</div>

<!-- ══ GLOBAL SEARCH OVERLAY (Ctrl+K) ══════════════════════ -->
<div id="global-search-overlay"
     style="position:fixed;inset:0;background:rgba(10,10,10,.55);z-index:800;
            display:none;align-items:flex-start;justify-content:center;
            padding-top:10vh;backdrop-filter:blur(6px)"
     role="dialog" aria-modal="true" aria-label="Global search">
  <div style="width:min(580px,92vw);background:var(--bg);border:1px solid var(--border);
              border-radius:var(--radius-lg);box-shadow:0 40px 100px -20px rgba(10,10,10,.5);
              overflow:hidden;animation:dropIn .25s ease both">
    <div style="display:flex;align-items:center;gap:.75rem;padding:1rem 1.25rem;
                border-bottom:1px solid var(--border)">
      <span style="font-size:1.1rem;color:var(--accent)">⌕</span>
      <input id="global-search-input" type="search"
             placeholder="Search closet, looks, planner…"
             autocomplete="off" maxlength="80"
             style="flex:1;background:transparent;border:0;outline:none;
                    font-size:1rem;color:var(--text)">
      <span onclick="F.search.closeOverlay()"
            style="padding:.2rem .5rem;border:1px solid var(--border);border-radius:6px;
                   font-size:.72rem;color:var(--muted);cursor:pointer;letter-spacing:.1em">ESC</span>
    </div>
    <div id="global-search-results"
         style="max-height:400px;overflow-y:auto">
      <div class="s-empty">Type to search your wardrobe…</div>
    </div>
    <div style="padding:.6rem 1.25rem;border-top:1px solid var(--border);
                font-size:.72rem;color:var(--muted);background:var(--surface);
                display:flex;gap:1.2rem;letter-spacing:.08em">
      <span><kbd style="background:var(--border);padding:1px 4px;border-radius:3px">↑↓</kbd> Navigate</span>
      <span><kbd style="background:var(--border);padding:1px 4px;border-radius:3px">Enter</kbd> Open</span>
      <span><kbd style="background:var(--border);padding:1px 4px;border-radius:3px">Esc</kbd> Close</span>
    </div>
  </div>
</div>`;

/* Inject floating elements and init search */
const initMobileNav = () => {
  const toggle = document.getElementById('nav-toggle');
  const menu = document.getElementById('nav-menu');
  if (!toggle || !menu) return;
  toggle.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  menu.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', () => {
      menu.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  const slot = F.$('#floating-slot');
  if (slot) {
    slot.innerHTML = F.renderFloating();
    document.body.classList.add('has-floating');
  }
  initMobileNav();
  setTimeout(() => F.search.init(), 0);
});