/* =============================================
   HOMI – Shared App JS
   ============================================= */

/* ── DARK MODE ── */
const html = document.documentElement;
const saved = localStorage.getItem('homi-theme') || 'light';
html.setAttribute('data-theme', saved);

function toggleTheme() {
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('homi-theme', next);
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    btn.textContent = next === 'dark' ? '☀️' : '🌙';
    btn.title = next === 'dark' ? 'Chế độ sáng' : 'Chế độ tối';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    btn.textContent = html.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
    btn.title = html.getAttribute('data-theme') === 'dark' ? 'Chế độ sáng' : 'Chế độ tối';
    btn.addEventListener('click', toggleTheme);
  });

  /* ── HAMBURGER MENU ── */
  document.querySelectorAll('.hamburger').forEach(btn => {
    btn.addEventListener('click', () => {
      const nav = document.querySelector('.navbar-collapse');
      if (!nav) return;
      nav.classList.toggle('open');
      btn.classList.toggle('open');
    });
  });

  // Close nav when clicking outside
  document.addEventListener('click', e => {
    if (!e.target.closest('.navbar')) {
      document.querySelector('.navbar-collapse')?.classList.remove('open');
      document.querySelector('.hamburger')?.classList.remove('open');
    }
  });

  /* ── SCROLL REVEAL ── */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    revealEls.forEach(el => io.observe(el));
  }

  /* ── BACK TO TOP ── */
  const btn = document.createElement('button');
  btn.className = 'back-to-top';
  btn.innerHTML = '↑';
  btn.title = 'Lên đầu trang';
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 400);
  });

  /* ── ACTIVE NAV LINK ── */
  const page = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.navbar-nav a').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });
});

/* ── TOAST SYSTEM ── */
window.toast = (function () {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function show(message, type = 'info', duration = 3500) {
    const c = getContainer();
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    el.innerHTML = `<span class="toast-icon">${icons[type] || 'ℹ️'}</span><span>${message}</span><button class="toast-close">×</button>`;
    el.querySelector('.toast-close').addEventListener('click', () => remove(el));
    c.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => remove(el), duration);
    return el;
  }

  function remove(el) {
    el.classList.remove('show');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
  }

  return {
    success: (msg, d) => show(msg, 'success', d),
    error:   (msg, d) => show(msg, 'error', d),
    warning: (msg, d) => show(msg, 'warning', d),
    info:    (msg, d) => show(msg, 'info', d),
  };
})();
