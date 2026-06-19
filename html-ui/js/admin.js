/* =============================================
   HOMI – Admin Panel JS (mock data, chưa nối API)
   ============================================= */

/* ── HELPERS ── */
function formatVND(n) { return n.toLocaleString('vi-VN') + 'đ'; }
function formatDate(d) { const [y, m, day] = d.split('-'); return `${day}/${m}/${y}`; }
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

/* ── STATUS MAPS ── */
const BOOKING_STATUS = {
  pending:     { label: 'Chờ xác nhận',  cls: 'badge-pending' },
  confirmed:   { label: 'Đã xác nhận',   cls: 'badge-confirmed' },
  checked_in:  { label: 'Đã nhận phòng', cls: 'badge-checked_in' },
  checked_out: { label: 'Đã trả phòng',  cls: 'badge-checked_out' },
  completed:   { label: 'Hoàn thành',    cls: 'badge-completed' },
  cancelled:   { label: 'Đã hủy',        cls: 'badge-cancelled' },
};
const ROLE_MAP = {
  admin:    { label: 'Quản trị viên', cls: 'badge-blue' },
  staff:    { label: 'Nhân viên',     cls: 'badge-pending' },
  customer: { label: 'Khách hàng',    cls: 'badge-confirmed' },
};

/* ── MOCK DATA ── */
let hotels = [
  { id: 1, name: 'Pullman Danang Beach Resort', address: '101 Võ Nguyên Giáp, Đà Nẵng', stars: 5, icon: '🏖️', status: 'active', created: '2025-01-12' },
  { id: 2, name: 'Vinpearl Resort Phú Quốc', address: 'Bãi Dài, Phú Quốc, Kiên Giang', stars: 5, icon: '🌴', status: 'active', created: '2025-02-03' },
  { id: 3, name: 'Intercontinental Hanoi', address: '1A Nghi Tàm, Tây Hồ, Hà Nội', stars: 5, icon: '🏛️', status: 'active', created: '2025-02-20' },
  { id: 4, name: 'Novotel Da Nang Premier', address: '36 Bạch Đằng, Đà Nẵng', stars: 4, icon: '🌆', status: 'active', created: '2025-03-15' },
  { id: 5, name: 'Mường Thanh Grand Sapa', address: 'Đường Mây, Sa Pa, Lào Cai', stars: 4, icon: '⛰️', status: 'hidden', created: '2025-04-02' },
  { id: 6, name: 'Anantara Hội An Resort', address: 'Phường Cẩm Châu, Hội An', stars: 5, icon: '🛶', status: 'active', created: '2025-05-18' },
];

let roomTypes = [
  { id: 1, name: 'Deluxe Ocean View', hotelId: 1, price: 1850000, capacity: 2, quantity: 12, status: 'active' },
  { id: 2, name: 'Premium Suite', hotelId: 1, price: 3200000, capacity: 3, quantity: 6, status: 'active' },
  { id: 3, name: 'Garden Villa', hotelId: 2, price: 4500000, capacity: 4, quantity: 8, status: 'active' },
  { id: 4, name: 'Beachfront Bungalow', hotelId: 2, price: 5200000, capacity: 2, quantity: 10, status: 'active' },
  { id: 5, name: 'Lake View Deluxe', hotelId: 3, price: 2100000, capacity: 2, quantity: 14, status: 'active' },
  { id: 6, name: 'Executive Suite', hotelId: 3, price: 3800000, capacity: 2, quantity: 5, status: 'active' },
  { id: 7, name: 'Superior Twin', hotelId: 4, price: 1100000, capacity: 2, quantity: 20, status: 'active' },
  { id: 8, name: 'Family Room', hotelId: 4, price: 1650000, capacity: 4, quantity: 9, status: 'inactive' },
  { id: 9, name: 'Mountain View Standard', hotelId: 5, price: 950000, capacity: 2, quantity: 16, status: 'active' },
  { id: 10, name: 'Pool Villa', hotelId: 6, price: 6500000, capacity: 4, quantity: 4, status: 'active' },
];

let bookings = [
  { id: 'BK-A3F8D2C1', customer: 'Nguyễn Văn An', email: 'an.nguyen@gmail.com', hotelId: 1, checkin: '2026-06-20', checkout: '2026-06-23', nights: 3, total: 5550000, status: 'confirmed' },
  { id: 'BK-B7E2A4F0', customer: 'Trần Thị Bình', email: 'binh.tran@gmail.com', hotelId: 4, checkin: '2026-07-05', checkout: '2026-07-07', nights: 2, total: 7500000, status: 'pending' },
  { id: 'BK-C9D3F1E2', customer: 'Lê Văn Cường', email: 'cuong.le@gmail.com', hotelId: 3, checkin: '2026-07-15', checkout: '2026-07-17', nights: 2, total: 4200000, status: 'pending' },
  { id: 'BK-D4F1B9C3', customer: 'Phạm Thị Dung', email: 'dung.pham@gmail.com', hotelId: 2, checkin: '2026-05-01', checkout: '2026-05-04', nights: 3, total: 12800000, status: 'cancelled' },
  { id: 'BK-E5A8C2D4', customer: 'Hoàng Văn Em', email: 'em.hoang@gmail.com', hotelId: 6, checkin: '2026-06-25', checkout: '2026-06-28', nights: 3, total: 19500000, status: 'checked_in' },
  { id: 'BK-F1B6D3A5', customer: 'Vũ Thị Phương', email: 'phuong.vu@gmail.com', hotelId: 5, checkin: '2026-04-10', checkout: '2026-04-12', nights: 2, total: 1900000, status: 'completed' },
  { id: 'BK-G8C4E1B7', customer: 'Đặng Văn Giang', email: 'giang.dang@gmail.com', hotelId: 1, checkin: '2026-08-01', checkout: '2026-08-05', nights: 4, total: 7400000, status: 'pending' },
  { id: 'BK-H2D9F5C8', customer: 'Bùi Thị Hoa', email: 'hoa.bui@gmail.com', hotelId: 3, checkin: '2026-06-12', checkout: '2026-06-13', nights: 1, total: 2100000, status: 'checked_out' },
  { id: 'BK-I6E3A8D2', customer: 'Ngô Văn Inh', email: 'inh.ngo@gmail.com', hotelId: 2, checkin: '2026-09-01', checkout: '2026-09-04', nights: 3, total: 13500000, status: 'confirmed' },
  { id: 'BK-J3F7C1E9', customer: 'Lý Thị Kim', email: 'kim.ly@gmail.com', hotelId: 4, checkin: '2026-03-20', checkout: '2026-03-22', nights: 2, total: 2200000, status: 'completed' },
  { id: 'BK-K9A2D6B4', customer: 'Trịnh Văn Long', email: 'long.trinh@gmail.com', hotelId: 6, checkin: '2026-10-05', checkout: '2026-10-08', nights: 3, total: 19500000, status: 'confirmed' },
  { id: 'BK-L5B8E2C7', customer: 'Đỗ Thị Mai', email: 'mai.do@gmail.com', hotelId: 1, checkin: '2026-02-14', checkout: '2026-02-16', nights: 2, total: 3700000, status: 'cancelled' },
];

let users = [
  { id: 1, name: 'Admin Homi', email: 'admin@homi.vn', role: 'admin', bookingCount: 0, joined: '2025-01-01', status: 'active' },
  { id: 2, name: 'Nguyễn Văn An', email: 'an.nguyen@gmail.com', role: 'customer', bookingCount: 5, joined: '2025-03-02', status: 'active' },
  { id: 3, name: 'Trần Thị Bình', email: 'binh.tran@gmail.com', role: 'customer', bookingCount: 2, joined: '2025-04-11', status: 'active' },
  { id: 4, name: 'Lê Văn Cường', email: 'cuong.le@gmail.com', role: 'customer', bookingCount: 1, joined: '2025-05-22', status: 'active' },
  { id: 5, name: 'Nhân viên Hỗ trợ', email: 'staff1@homi.vn', role: 'staff', bookingCount: 0, joined: '2025-02-10', status: 'active' },
  { id: 6, name: 'Phạm Thị Dung', email: 'dung.pham@gmail.com', role: 'customer', bookingCount: 4, joined: '2025-06-01', status: 'locked' },
  { id: 7, name: 'Hoàng Văn Em', email: 'em.hoang@gmail.com', role: 'customer', bookingCount: 3, joined: '2025-07-19', status: 'active' },
  { id: 8, name: 'Vũ Thị Phương', email: 'phuong.vu@gmail.com', role: 'customer', bookingCount: 1, joined: '2025-08-05', status: 'active' },
  { id: 9, name: 'Đặng Văn Giang', email: 'giang.dang@gmail.com', role: 'customer', bookingCount: 2, joined: '2025-09-14', status: 'active' },
  { id: 10, name: 'Bùi Thị Hoa', email: 'hoa.bui@gmail.com', role: 'customer', bookingCount: 1, joined: '2025-10-30', status: 'locked' },
];

let nextHotelId = 7, nextRoomTypeId = 11;
const bookingState = { page: 1 };

function hotelById(id) { return hotels.find(h => h.id === id); }
function hotelName(id) { return hotelById(id)?.name || '—'; }

/* ── MODAL ── */
function openModal(title, bodyHtml, footerHtml, opts = {}) {
  const root = document.getElementById('modalRoot');
  root.innerHTML = `
    <div class="modal-overlay open" id="activeModal">
      <div class="modal-box ${opts.large ? 'modal-lg' : ''}">
        <div class="modal-header"><h3>${title}</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <div class="modal-body">${bodyHtml}</div>
        ${footerHtml ? `<div class="modal-footer">${footerHtml}</div>` : ''}
      </div>
    </div>`;
  document.getElementById('activeModal').addEventListener('click', e => { if (e.target.id === 'activeModal') closeModal(); });
}
function closeModal() { document.getElementById('modalRoot').innerHTML = ''; }

/* ── PAGINATION ── */
function renderPagination(containerId, page, totalPages, onPage) {
  const el = document.getElementById(containerId);
  if (!el) return;
  if (totalPages <= 1) { el.innerHTML = ''; return; }
  let html = `<button class="page-btn ${page === 1 ? 'disabled' : ''}" data-p="${page - 1}">‹</button>`;
  for (let i = 1; i <= totalPages; i++) html += `<button class="page-btn ${i === page ? 'active' : ''}" data-p="${i}">${i}</button>`;
  html += `<button class="page-btn ${page === totalPages ? 'disabled' : ''}" data-p="${page + 1}">›</button>`;
  el.innerHTML = html;
  el.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', () => { const p = parseInt(btn.dataset.p); if (p >= 1 && p <= totalPages) onPage(p); });
  });
}

/* ===================================================
   DASHBOARD
   =================================================== */
function renderDashboard() {
  const pendingCount = bookings.filter(b => b.status === 'pending').length;
  const activeHotels = hotels.filter(h => h.status === 'active').length;
  document.getElementById('kpiHotels').textContent = activeHotels;
  document.getElementById('kpiUsers').textContent = users.length.toLocaleString('vi-VN');
  document.getElementById('kpiBookings').textContent = bookings.length;
  const revenue = bookings.filter(b => !['cancelled'].includes(b.status)).reduce((s, b) => s + b.total, 0);
  document.getElementById('kpiRevenue').textContent = (revenue / 1000000).toFixed(1) + 'M đ';

  // recent bookings (latest 4)
  const recent = [...bookings].slice(-4).reverse();
  document.getElementById('recentBookingsBody').innerHTML = recent.map(b => {
    const st = BOOKING_STATUS[b.status];
    return `<tr>
      <td><strong style="color:var(--blue)">${b.id}</strong></td>
      <td>${b.customer}</td>
      <td>${hotelName(b.hotelId)}</td>
      <td>${formatDate(b.checkin)}</td>
      <td>${formatVND(b.total)}</td>
      <td><span class="badge ${st.cls}">${st.label}</span></td>
      <td>${b.status === 'pending'
        ? `<button class="btn btn-primary btn-sm" onclick="approveBooking('${b.id}')">Duyệt</button>`
        : `<button class="btn btn-ghost btn-sm" onclick="viewBooking('${b.id}')">Xem</button>`}</td>
    </tr>`;
  }).join('');

  // top hotels by revenue
  const byHotel = hotels.map(h => {
    const hb = bookings.filter(b => b.hotelId === h.id && b.status !== 'cancelled');
    const rev = hb.reduce((s, b) => s + b.total, 0);
    return { hotel: h, count: hb.length, rev };
  }).sort((a, b) => b.rev - a.rev).slice(0, 3);
  const maxRev = Math.max(...byHotel.map(x => x.rev), 1);
  const barColors = ['var(--blue)', 'var(--green)', 'var(--orange)'];
  document.getElementById('topHotelsBody').innerHTML = byHotel.map((x, i) => `
    <tr>
      <td>${i + 1}</td>
      <td><div class="entity-cell"><div class="entity-icon">${x.hotel.icon}</div>${x.hotel.name}</div></td>
      <td>${x.count}</td>
      <td style="font-weight:700;color:var(--blue)">${(x.rev / 1000000).toFixed(1)}M đ</td>
      <td>
        <div style="background:var(--bg2);border-radius:4px;height:8px;width:100%;overflow:hidden">
          <div style="width:${Math.round(x.rev / maxRev * 100)}%;height:100%;background:${barColors[i] || 'var(--blue)'};border-radius:4px"></div>
        </div>
        <span style="font-size:.78rem;color:var(--muted)">${Math.round(x.rev / maxRev * 100)}%</span>
      </td>
    </tr>`).join('');

  document.getElementById('bookingsBadge').textContent = pendingCount;
  document.getElementById('bookingsBadge').style.display = pendingCount ? 'inline-flex' : 'none';
}

function buildBarChart() {
  const data = [12.4, 18.2, 9.8, 22.1, 15.6, 19.3, 25.4];
  const labels = ['13/06', '14/06', '15/06', '16/06', '17/06', '18/06', '19/06'];
  const maxVal = Math.max(...data);
  const chart = document.getElementById('barChart');
  if (!chart) return;
  data.forEach((v, i) => {
    const b = document.createElement('div');
    b.className = 'bar';
    b.style.height = (v / maxVal * 100) + '%';
    const opacity = 0.5 + (v / maxVal * 0.5);
    b.style.background = `rgba(47,128,237,${opacity})`;
    b.title = `${labels[i]}: ${v} triệu đ`;
    b.addEventListener('mouseenter', () => toast.info(`${labels[i]}: ${v} triệu đồng`));
    chart.appendChild(b);
  });
}

function buildDonut() {
  const counts = { confirmed: 0, completed: 0, pending: 0, cancelled: 0, other: 0 };
  bookings.forEach(b => {
    if (b.status === 'confirmed') counts.confirmed++;
    else if (b.status === 'completed') counts.completed++;
    else if (b.status === 'pending') counts.pending++;
    else if (b.status === 'cancelled') counts.cancelled++;
    else counts.other++;
  });
  const total = bookings.length;
  const pct = k => Math.round(counts[k] / total * 100);
  let acc = 0;
  const seg = (k, color) => { const from = acc; acc += pct(k); return `${color} ${from}% ${acc}%`; };
  const gradient = [
    seg('confirmed', 'var(--blue)'),
    seg('completed', 'var(--green)'),
    seg('pending', 'var(--orange)'),
    seg('cancelled', 'var(--red)'),
  ].join(', ');
  document.getElementById('donutChart').style.background = `conic-gradient(${gradient})`;
  document.getElementById('donutChart').innerHTML = `<span>${total}</span>`;
  document.getElementById('donutLegend').innerHTML = `
    <div class="legend-item"><div class="legend-dot" style="background:var(--blue)"></div>Xác nhận (${pct('confirmed')}%)</div>
    <div class="legend-item"><div class="legend-dot" style="background:var(--green)"></div>Hoàn thành (${pct('completed')}%)</div>
    <div class="legend-item"><div class="legend-dot" style="background:var(--orange)"></div>Chờ xác nhận (${pct('pending')}%)</div>
    <div class="legend-item"><div class="legend-dot" style="background:var(--red)"></div>Đã hủy (${pct('cancelled')}%)</div>`;
}

/* ===================================================
   BOOKINGS
   =================================================== */
function renderBookings() {
  const searchVal = (document.getElementById('bookingSearch').value || '').toLowerCase();
  const statusVal = document.getElementById('bookingStatusFilter').value;
  let filtered = bookings.filter(b =>
    (b.id.toLowerCase().includes(searchVal) || b.customer.toLowerCase().includes(searchVal) || hotelName(b.hotelId).toLowerCase().includes(searchVal)) &&
    (statusVal === 'all' || b.status === statusVal)
  );
  const pageSize = 6;
  const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
  if (bookingState.page > totalPages) bookingState.page = totalPages;
  const start = (bookingState.page - 1) * pageSize;
  const pageItems = filtered.slice(start, start + pageSize);
  const tbody = document.getElementById('bookingsTableBody');

  if (!pageItems.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="icon">🔍</div><h3>Không tìm thấy đặt phòng</h3><p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p></div></td></tr>`;
  } else {
    tbody.innerHTML = pageItems.map(b => {
      const st = BOOKING_STATUS[b.status];
      return `<tr>
        <td><strong style="color:var(--blue)">${b.id}</strong></td>
        <td>${b.customer}</td>
        <td>${hotelName(b.hotelId)}</td>
        <td>${formatDate(b.checkin)} → ${formatDate(b.checkout)}</td>
        <td>${b.nights} đêm</td>
        <td style="font-weight:700">${formatVND(b.total)}</td>
        <td><span class="badge ${st.cls}">${st.label}</span></td>
        <td><div class="row-actions">
          <button class="icon-action" title="Xem chi tiết" onclick="viewBooking('${b.id}')">👁️</button>
          ${b.status === 'pending' ? `<button class="icon-action success" title="Duyệt" onclick="approveBooking('${b.id}')">✔️</button>` : ''}
          ${['pending', 'confirmed'].includes(b.status) ? `<button class="icon-action danger" title="Hủy đặt phòng" onclick="cancelBooking('${b.id}')">✖️</button>` : ''}
        </div></td>
      </tr>`;
    }).join('');
  }
  document.getElementById('bookingsCount').textContent = filtered.length;
  renderPagination('bookingsPagination', bookingState.page, totalPages, p => { bookingState.page = p; renderBookings(); });
}

function viewBooking(id) {
  const b = bookings.find(x => x.id === id);
  if (!b) return;
  const st = BOOKING_STATUS[b.status];
  openModal(`Chi tiết đặt phòng ${b.id}`, `
    <div class="info-grid">
      <div class="info-item"><label>Khách hàng</label><p>${b.customer}</p></div>
      <div class="info-item"><label>Email</label><p>${b.email}</p></div>
      <div class="info-item"><label>Khách sạn</label><p>${hotelName(b.hotelId)}</p></div>
      <div class="info-item"><label>Trạng thái</label><p><span class="badge ${st.cls}">${st.label}</span></p></div>
      <div class="info-item"><label>Check-in</label><p>${formatDate(b.checkin)}</p></div>
      <div class="info-item"><label>Check-out</label><p>${formatDate(b.checkout)}</p></div>
      <div class="info-item"><label>Số đêm</label><p>${b.nights} đêm</p></div>
      <div class="info-item"><label>Tổng tiền</label><p style="font-weight:700;color:var(--blue)">${formatVND(b.total)}</p></div>
    </div>`,
    `<button class="btn btn-outline" onclick="closeModal()">Đóng</button>
     ${b.status === 'pending' ? `<button class="btn btn-primary" onclick="approveBooking('${b.id}');closeModal()">Duyệt đặt phòng</button>` : ''}`
  );
}

function approveBooking(id) {
  const b = bookings.find(x => x.id === id);
  if (!b) return;
  b.status = 'confirmed';
  toast.success(`Đã xác nhận đặt phòng ${id}`);
  renderBookings(); renderDashboard();
}

function cancelBooking(id) {
  if (!confirm(`Xác nhận hủy đặt phòng ${id}?`)) return;
  const b = bookings.find(x => x.id === id);
  if (!b) return;
  b.status = 'cancelled';
  toast.warning(`Đã hủy đặt phòng ${id}`);
  renderBookings(); renderDashboard();
}

/* ===================================================
   HOTELS
   =================================================== */
function renderHotels() {
  const searchVal = (document.getElementById('hotelSearch').value || '').toLowerCase();
  const statusVal = document.getElementById('hotelStatusFilter').value;
  const filtered = hotels.filter(h =>
    (h.name.toLowerCase().includes(searchVal) || h.address.toLowerCase().includes(searchVal)) &&
    (statusVal === 'all' || h.status === statusVal)
  );
  const tbody = document.getElementById('hotelsTableBody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="icon">🏨</div><h3>Không tìm thấy khách sạn</h3><p>Thử thay đổi bộ lọc hoặc thêm khách sạn mới</p></div></td></tr>`;
  } else {
    tbody.innerHTML = filtered.map(h => {
      const roomCount = roomTypes.filter(r => r.hotelId === h.id).length;
      return `<tr>
        <td><div class="entity-cell"><div class="entity-icon">${h.icon}</div><div><div class="entity-name">${h.name}</div><div class="entity-sub">${h.address}</div></div></div></td>
        <td><span class="stars">${'★'.repeat(h.stars)}</span><span class="stars-muted">${'★'.repeat(5 - h.stars)}</span></td>
        <td>${roomCount} loại</td>
        <td>
          <label class="switch"><input type="checkbox" ${h.status === 'active' ? 'checked' : ''} onchange="toggleHotelStatus(${h.id})"><span class="slider"></span></label>
          <span class="badge ${h.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}" style="margin-left:.5rem">${h.status === 'active' ? 'Hoạt động' : 'Đã ẩn'}</span>
        </td>
        <td>${formatDate(h.created)}</td>
        <td><div class="row-actions"><button class="icon-action" title="Sửa" onclick="openHotelModal(${h.id})">✏️</button></div></td>
      </tr>`;
    }).join('');
  }
  document.getElementById('hotelsCount').textContent = filtered.length;

  // keep room-type hotel filter / select options in sync
  const opts = hotels.map(h => `<option value="${h.id}">${h.name}</option>`).join('');
  const f = document.getElementById('roomHotelFilter');
  if (f) f.innerHTML = `<option value="all">Tất cả khách sạn</option>${opts}`;
}

function toggleHotelStatus(id) {
  const h = hotelById(id);
  if (!h) return;
  h.status = h.status === 'active' ? 'hidden' : 'active';
  toast.success(h.status === 'active' ? `Đã hiện khách sạn "${h.name}"` : `Đã ẩn khách sạn "${h.name}"`);
  renderHotels(); renderDashboard();
}

function openHotelModal(id) {
  const h = id ? hotelById(id) : null;
  openModal(h ? 'Sửa khách sạn' : 'Thêm khách sạn mới', `
    <form id="hotelForm">
      <div class="form-group">
        <label class="form-label">Tên khách sạn<span class="req">*</span></label>
        <input class="form-control" id="hf_name" required value="${h ? escapeHtml(h.name) : ''}">
      </div>
      <div class="form-group">
        <label class="form-label">Địa chỉ<span class="req">*</span></label>
        <input class="form-control" id="hf_address" required value="${h ? escapeHtml(h.address) : ''}">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Hạng sao</label>
          <select class="form-control" id="hf_stars">
            ${[5, 4, 3, 2, 1].map(s => `<option value="${s}" ${h && h.stars === s ? 'selected' : ''}>${s} sao</option>`).join('')}
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Trạng thái</label>
          <select class="form-control" id="hf_status">
            <option value="active" ${!h || h.status === 'active' ? 'selected' : ''}>Hoạt động</option>
            <option value="hidden" ${h && h.status === 'hidden' ? 'selected' : ''}>Đã ẩn</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Mô tả</label>
        <textarea class="form-control" id="hf_desc" placeholder="Giới thiệu ngắn về khách sạn...">${h ? '' : ''}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Ảnh đại diện</label>
        <div class="upload-box" onclick="toast.info('Chức năng tải ảnh đang phát triển')"><div class="icon">📷</div>Kéo thả hoặc bấm để tải ảnh lên</div>
      </div>
    </form>`,
    `<button class="btn btn-outline" onclick="closeModal()">Hủy</button>
     <button class="btn btn-primary" onclick="saveHotel(${h ? h.id : 'null'})">${h ? 'Lưu thay đổi' : 'Thêm khách sạn'}</button>`,
    { large: false }
  );
}

function saveHotel(id) {
  const name = document.getElementById('hf_name').value.trim();
  const address = document.getElementById('hf_address').value.trim();
  if (!name || !address) { toast.error('Vui lòng nhập đầy đủ tên và địa chỉ khách sạn'); return; }
  const stars = parseInt(document.getElementById('hf_stars').value);
  const status = document.getElementById('hf_status').value;
  if (id) {
    const h = hotelById(id);
    Object.assign(h, { name, address, stars, status });
    toast.success(`Đã cập nhật khách sạn "${name}"`);
  } else {
    hotels.push({ id: nextHotelId++, name, address, stars, status, icon: '🏨', created: new Date().toISOString().slice(0, 10) });
    toast.success(`Đã thêm khách sạn "${name}"`);
  }
  closeModal(); renderHotels(); renderDashboard();
}

/* ===================================================
   ROOM TYPES
   =================================================== */
function renderRoomTypes() {
  const searchVal = (document.getElementById('roomSearch').value || '').toLowerCase();
  const hotelFilter = document.getElementById('roomHotelFilter').value;
  const filtered = roomTypes.filter(r =>
    r.name.toLowerCase().includes(searchVal) &&
    (hotelFilter === 'all' || String(r.hotelId) === hotelFilter)
  );
  const tbody = document.getElementById('roomTypesTableBody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="icon">🛏️</div><h3>Không tìm thấy loại phòng</h3><p>Thử thay đổi bộ lọc hoặc thêm loại phòng mới</p></div></td></tr>`;
  } else {
    tbody.innerHTML = filtered.map(r => `
      <tr>
        <td><div class="entity-cell"><div class="entity-icon">🛏️</div><div class="entity-name">${r.name}</div></div></td>
        <td>${hotelName(r.hotelId)}</td>
        <td>${r.capacity} khách</td>
        <td style="font-weight:700">${formatVND(r.price)}<span style="color:var(--muted);font-weight:400">/đêm</span></td>
        <td>${r.quantity} phòng</td>
        <td>
          <label class="switch"><input type="checkbox" ${r.status === 'active' ? 'checked' : ''} onchange="toggleRoomTypeStatus(${r.id})"><span class="slider"></span></label>
          <span class="badge ${r.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}" style="margin-left:.5rem">${r.status === 'active' ? 'Hoạt động' : 'Đã ẩn'}</span>
        </td>
        <td><div class="row-actions"><button class="icon-action" title="Sửa" onclick="openRoomTypeModal(${r.id})">✏️</button></div></td>
      </tr>`).join('');
  }
  document.getElementById('roomTypesCount').textContent = filtered.length;
}

function toggleRoomTypeStatus(id) {
  const r = roomTypes.find(x => x.id === id);
  if (!r) return;
  r.status = r.status === 'active' ? 'inactive' : 'active';
  toast.success(r.status === 'active' ? `Đã kích hoạt loại phòng "${r.name}"` : `Đã ẩn loại phòng "${r.name}"`);
  renderRoomTypes();
}

function openRoomTypeModal(id) {
  const r = id ? roomTypes.find(x => x.id === id) : null;
  const hotelOpts = hotels.map(h => `<option value="${h.id}" ${r && r.hotelId === h.id ? 'selected' : ''}>${h.name}</option>`).join('');
  openModal(r ? 'Sửa loại phòng' : 'Thêm loại phòng mới', `
    <form id="roomForm">
      <div class="form-group">
        <label class="form-label">Tên loại phòng<span class="req">*</span></label>
        <input class="form-control" id="rf_name" required value="${r ? escapeHtml(r.name) : ''}">
      </div>
      <div class="form-group">
        <label class="form-label">Khách sạn<span class="req">*</span></label>
        <select class="form-control" id="rf_hotel">${hotelOpts}</select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Giá / đêm (đ)<span class="req">*</span></label>
          <input class="form-control" type="number" min="0" id="rf_price" required value="${r ? r.price : ''}">
        </div>
        <div class="form-group">
          <label class="form-label">Sức chứa (khách)</label>
          <input class="form-control" type="number" min="1" id="rf_capacity" value="${r ? r.capacity : 2}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Số lượng phòng</label>
        <input class="form-control" type="number" min="0" id="rf_quantity" value="${r ? r.quantity : 1}">
      </div>
      <div class="form-group">
        <label class="form-label">Tiện ích</label>
        <div class="check-grid">
          ${['Wifi miễn phí', 'Điều hòa', 'TV màn hình phẳng', 'Minibar', 'Bồn tắm', 'Ban công'].map((a, i) =>
            `<label class="check-item"><input type="checkbox" ${!r || i < 3 ? 'checked' : ''}> ${a}</label>`).join('')}
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Ảnh phòng</label>
        <div class="upload-box" onclick="toast.info('Chức năng tải ảnh đang phát triển')"><div class="icon">📷</div>Kéo thả hoặc bấm để tải ảnh lên</div>
      </div>
    </form>`,
    `<button class="btn btn-outline" onclick="closeModal()">Hủy</button>
     <button class="btn btn-primary" onclick="saveRoomType(${r ? r.id : 'null'})">${r ? 'Lưu thay đổi' : 'Thêm loại phòng'}</button>`,
    { large: true }
  );
}

function saveRoomType(id) {
  const name = document.getElementById('rf_name').value.trim();
  const hotelId = parseInt(document.getElementById('rf_hotel').value);
  const price = parseInt(document.getElementById('rf_price').value);
  const capacity = parseInt(document.getElementById('rf_capacity').value) || 1;
  const quantity = parseInt(document.getElementById('rf_quantity').value) || 0;
  if (!name || !price) { toast.error('Vui lòng nhập đầy đủ tên loại phòng và giá'); return; }
  if (id) {
    const r = roomTypes.find(x => x.id === id);
    Object.assign(r, { name, hotelId, price, capacity, quantity });
    toast.success(`Đã cập nhật loại phòng "${name}"`);
  } else {
    roomTypes.push({ id: nextRoomTypeId++, name, hotelId, price, capacity, quantity, status: 'active' });
    toast.success(`Đã thêm loại phòng "${name}"`);
  }
  closeModal(); renderRoomTypes(); renderHotels();
}

/* ===================================================
   USERS
   =================================================== */
function renderUsers() {
  const searchVal = (document.getElementById('userSearch').value || '').toLowerCase();
  const roleVal = document.getElementById('userRoleFilter').value;
  const statusVal = document.getElementById('userStatusFilter').value;
  const filtered = users.filter(u =>
    (u.name.toLowerCase().includes(searchVal) || u.email.toLowerCase().includes(searchVal)) &&
    (roleVal === 'all' || u.role === roleVal) &&
    (statusVal === 'all' || u.status === statusVal)
  );
  const tbody = document.getElementById('usersTableBody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="icon">👥</div><h3>Không tìm thấy người dùng</h3><p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p></div></td></tr>`;
  } else {
    tbody.innerHTML = filtered.map(u => {
      const role = ROLE_MAP[u.role];
      return `<tr>
        <td><div class="entity-cell"><div class="avatar-sm">${u.name.charAt(0)}</div><div><div class="entity-name">${u.name}</div><div class="entity-sub">${u.email}</div></div></div></td>
        <td><span class="badge ${role.cls}">${role.label}</span></td>
        <td>${u.bookingCount}</td>
        <td>${formatDate(u.joined)}</td>
        <td><span class="badge ${u.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}">${u.status === 'active' ? 'Hoạt động' : 'Đã khóa'}</span></td>
        <td><div class="row-actions">
          <button class="icon-action" title="Xem hồ sơ" onclick="viewUser(${u.id})">👁️</button>
          ${u.role !== 'admin' ? `<button class="icon-action ${u.status === 'active' ? 'danger' : 'success'}" title="${u.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'}" onclick="toggleUserLock(${u.id})">${u.status === 'active' ? '🔒' : '🔓'}</button>` : ''}
        </div></td>
      </tr>`;
    }).join('');
  }
  document.getElementById('usersCount').textContent = filtered.length;
}

function viewUser(id) {
  const u = users.find(x => x.id === id);
  if (!u) return;
  const role = ROLE_MAP[u.role];
  openModal('Hồ sơ người dùng', `
    <div class="flex-center" style="gap:1rem;margin-bottom:1.25rem">
      <div class="avatar-sm" style="width:56px;height:56px;font-size:1.4rem">${u.name.charAt(0)}</div>
      <div><div style="font-weight:700;font-size:1.05rem">${u.name}</div><div class="text-muted" style="font-size:.85rem">${u.email}</div></div>
    </div>
    <div class="info-grid">
      <div class="info-item"><label>Vai trò</label><p><span class="badge ${role.cls}">${role.label}</span></p></div>
      <div class="info-item"><label>Trạng thái</label><p><span class="badge ${u.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}">${u.status === 'active' ? 'Hoạt động' : 'Đã khóa'}</span></p></div>
      <div class="info-item"><label>Số đặt phòng</label><p>${u.bookingCount}</p></div>
      <div class="info-item"><label>Ngày tham gia</label><p>${formatDate(u.joined)}</p></div>
    </div>`,
    `<button class="btn btn-outline" onclick="closeModal()">Đóng</button>`
  );
}

function toggleUserLock(id) {
  const u = users.find(x => x.id === id);
  if (!u) return;
  const locking = u.status === 'active';
  if (!confirm(locking ? `Khóa tài khoản "${u.name}"?` : `Mở khóa tài khoản "${u.name}"?`)) return;
  u.status = locking ? 'locked' : 'active';
  toast[locking ? 'warning' : 'success'](locking ? `Đã khóa tài khoản "${u.name}"` : `Đã mở khóa tài khoản "${u.name}"`);
  renderUsers();
}

/* ===================================================
   SETTINGS
   =================================================== */
function switchSettingsTab(tab, el) {
  document.querySelectorAll('[id^="settings-"]').forEach(s => s.style.display = 'none');
  document.getElementById('settings-' + tab).style.display = 'block';
  document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

/* ===================================================
   NAVIGATION / SHELL
   =================================================== */
function switchSection(id, el) {
  document.querySelectorAll('[id^="sec-"]').forEach(s => s.style.display = 'none');
  document.getElementById('sec-' + id).style.display = 'block';
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  if (el) el.classList.add('active');
  document.querySelector('.admin-sidebar')?.classList.remove('mobile-open');
}

function closeAllDropdowns(except) {
  document.querySelectorAll('.admin-dropdown.open').forEach(d => { if (d !== except) d.classList.remove('open'); });
}

document.addEventListener('DOMContentLoaded', () => {
  buildBarChart();
  buildDonut();
  renderHotels();
  renderRoomTypes();
  renderBookings();
  renderUsers();
  renderDashboard();

  // filters
  ['bookingSearch', 'bookingStatusFilter'].forEach(id => document.getElementById(id)?.addEventListener('input', () => { bookingState.page = 1; renderBookings(); }));
  document.getElementById('bookingStatusFilter')?.addEventListener('change', () => { bookingState.page = 1; renderBookings(); });
  ['hotelSearch', 'hotelStatusFilter'].forEach(id => { const e = document.getElementById(id); e?.addEventListener('input', renderHotels); e?.addEventListener('change', renderHotels); });
  ['roomSearch', 'roomHotelFilter'].forEach(id => { const e = document.getElementById(id); e?.addEventListener('input', renderRoomTypes); e?.addEventListener('change', renderRoomTypes); });
  ['userSearch', 'userRoleFilter', 'userStatusFilter'].forEach(id => { const e = document.getElementById(id); e?.addEventListener('input', renderUsers); e?.addEventListener('change', renderUsers); });

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

  // global search bar -> jump to bookings filtered by text (simple demo)
  document.getElementById('globalSearch')?.addEventListener('keydown', e => {
    if (e.key === 'Enter' && e.target.value.trim()) {
      switchSection('bookings', document.querySelector('.nav-link[data-section="bookings"]'));
      document.getElementById('bookingSearch').value = e.target.value.trim();
      renderBookings();
    }
  });
});
