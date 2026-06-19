/* =============================================
   HOMI – Admin Panel JS (giao diện thật, dữ liệu lấy từ Blade/PHP)
   ============================================= */

function closeAllDropdowns(except) {
  document.querySelectorAll('.admin-dropdown.open').forEach(d => { if (d !== except) d.classList.remove('open'); });
}

function switchSettingsTab(tab, el) {
  document.querySelectorAll('[id^="settings-"]').forEach(s => s.style.display = 'none');
  document.getElementById('settings-' + tab).style.display = 'block';
  document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

/* ── Biểu đồ cột doanh thu/đặt phòng 7 ngày (nhận data thật qua window.__chartData) ── */
function buildBarChart(data, labels) {
  const chart = document.getElementById('barChart');
  if (!chart || !data || !data.length) return;
  const maxVal = Math.max(...data, 1);
  data.forEach((v, i) => {
    const b = document.createElement('div');
    b.className = 'bar';
    b.style.height = (v / maxVal * 100) + '%';
    const opacity = 0.5 + (v / maxVal * 0.5);
    b.style.background = `rgba(47,128,237,${opacity})`;
    b.title = `${labels[i] || ''}: ${v}`;
    chart.appendChild(b);
  });
}

/* ── Donut trạng thái đặt phòng (nhận mảng {label, count, color}) ── */
function buildDonut(segments) {
  const donut = document.getElementById('donutChart');
  const legend = document.getElementById('donutLegend');
  if (!donut || !segments) return;
  const total = segments.reduce((s, x) => s + x.count, 0) || 1;
  let acc = 0;
  const gradient = segments.map(s => {
    const from = acc;
    acc += (s.count / total * 100);
    return `${s.color} ${from}% ${acc}%`;
  }).join(', ');
  donut.style.background = `conic-gradient(${gradient})`;
  donut.innerHTML = `<span>${total}</span>`;
  legend.innerHTML = segments.map(s => `
    <div class="legend-item"><div class="legend-dot" style="background:${s.color}"></div>${s.label} (${Math.round(s.count / total * 100)}%)</div>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  // sidebar toggle (desktop collapse / mobile slide)
  document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    const sidebar = document.querySelector('.admin-sidebar');
    if (window.innerWidth <= 1100) sidebar.classList.toggle('mobile-open');
    else sidebar.classList.toggle('collapsed');
  });

  // profile / notif dropdowns
  document.getElementById('profileBtn')?.addEventListener('click', e => {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    closeAllDropdowns(dd); dd.classList.toggle('open');
  });
  document.getElementById('notifBtn')?.addEventListener('click', e => {
    e.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    closeAllDropdowns(dd); dd.classList.toggle('open');
  });
  document.addEventListener('click', () => closeAllDropdowns());

  // dashboard charts (data injected by Blade via window.__dashboardData)
  if (window.__dashboardData) {
    buildBarChart(window.__dashboardData.chartValues, window.__dashboardData.chartLabels);
    buildDonut(window.__dashboardData.donutSegments);
  }
});
