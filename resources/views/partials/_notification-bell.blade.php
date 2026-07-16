{{-- Chuông thông báo — dùng chung admin/staff/customer --}}
{{-- Polling mỗi 30s qua /notifications/poll, không cần reload trang --}}
<div class="relative" id="notif-wrapper">
    <button onclick="toggleNotifDropdown()"
        class="relative grid h-10 w-10 place-items-center rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
        aria-label="Thông báo">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        <span id="notif-badge"
            class="absolute top-1 right-1 hidden h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"></span>
    </button>

    <div id="notif-dropdown"
        class="absolute right-0 top-full z-50 mt-2 hidden w-80 rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <span class="text-sm font-bold">Thông báo</span>
            <button onclick="markAllRead()" class="text-xs text-primary hover:underline">Đọc tất cả</button>
        </div>
        <div id="notif-list" class="max-h-72 overflow-y-auto">
            <div class="px-4 py-6 text-center text-sm text-slate-400">Đang tải...</div>
        </div>
    </div>
</div>

<script>
(function () {
    const POLL_URL  = '{{ route("notifications.poll") }}';
    const READ_URL  = '{{ route("notifications.read.ajax") }}';
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

    const badge     = document.getElementById('notif-badge');
    const list      = document.getElementById('notif-list');
    const dropdown  = document.getElementById('notif-dropdown');
    const wrapper   = document.getElementById('notif-wrapper');

    let items = [];

    function renderList() {
        if (items.length === 0) {
            list.innerHTML = '<div class="px-4 py-6 text-center text-sm text-slate-400">Không có thông báo mới.</div>';
            return;
        }
        list.innerHTML = items.map(n => `
            <button onclick="markRead('${n.id}','${n.url}')"
                class="w-full px-4 py-3 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-0">
                <div class="font-medium text-slate-800 dark:text-slate-100">${n.message}</div>
                <div class="mt-0.5 text-xs text-slate-400">${n.ago}</div>
            </button>`).join('');
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    }

    // Badge "Chat khách hàng" ở sidebar admin/staff — không có trên layout
    // customer nên chỉ cập nhật khi phần tử tồn tại trên trang.
    function updateChatBadge(count) {
        const chatBadge = document.getElementById('chat-badge');
        if (!chatBadge || count === null || count === undefined) return;
        chatBadge.textContent = count;
        chatBadge.classList.toggle('hidden', count <= 0);
    }

    async function poll() {
        try {
            const res  = await fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            items = data.items ?? [];
            updateBadge(data.count ?? 0);
            updateChatBadge(data.chat_unread);
            renderList();
        } catch (_) {}
    }

    window.toggleNotifDropdown = function () {
        dropdown.classList.toggle('hidden');
    };

    window.markRead = async function (id, url) {
        await fetch(READ_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ id }),
        });
        items = items.filter(n => n.id !== id);
        updateBadge(items.length);
        renderList();
        if (url && url !== '#') window.location.href = url;
    };

    window.markAllRead = async function () {
        await fetch(READ_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({}),
        });
        items = [];
        updateBadge(0);
        renderList();
    };

    // Đóng dropdown khi click ngoài
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) dropdown.classList.add('hidden');
    });

    // Poll ngay khi load, sau đó mỗi 30s
    poll();
    setInterval(poll, 30000);
})();
</script>
