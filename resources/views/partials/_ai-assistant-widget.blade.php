{{-- Trợ lý ảo AI — widget chat nổi, độc lập với khung "Hỗ trợ" (chat nhân
     viên) ở customer.chat.*. Chỉ hiển thị trên layout khách/công khai, không
     nhúng vào layout admin/staff. Lịch sử hội thoại giữ ở sessionStorage của
     trình duyệt (mất khi đóng tab), không lưu ở server. --}}
<div id="ai-assistant-widget" class="fixed bottom-5 right-5 z-50">
    <button type="button" id="ai-assistant-toggle" aria-label="Mở trợ lý ảo"
        class="grid h-16 w-16 place-items-center rounded-full bg-primary text-white shadow-lg transition hover:scale-105 hover:brightness-110">
        {{-- Mặt robot: ăng-ten, đầu bo góc, 2 mắt, miệng — vẽ bằng SVG nên không phụ thuộc ảnh ngoài --}}
        <svg id="ai-assistant-icon-open" class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path stroke-linecap="round" d="M12 2.5v2"/>
            <circle cx="12" cy="2" r="1" fill="currentColor" stroke="none"/>
            <rect x="4" y="7.5" width="16" height="12" rx="4" fill="currentColor" fill-opacity="0.15"/>
            <rect x="4" y="7.5" width="16" height="12" rx="4"/>
            <path stroke-linecap="round" d="M1.5 12v3M22.5 12v3"/>
            <circle cx="9" cy="13.5" r="1.4" fill="currentColor" stroke="none"/>
            <circle cx="15" cy="13.5" r="1.4" fill="currentColor" stroke="none"/>
            <path stroke-linecap="round" d="M9 17h6"/>
        </svg>
        <svg id="ai-assistant-icon-close" class="hidden h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
        </svg>
    </button>

    <div id="ai-assistant-panel"
        class="absolute bottom-[calc(100%+12px)] right-0 hidden w-[min(360px,calc(100vw-40px))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between bg-primary px-4 py-3 text-white">
            <div>
                <p class="text-sm font-bold">Trợ lý ảo Homi</p>
                <p class="text-xs text-white/80">Hỏi về phòng, giá, tình trạng trống</p>
            </div>
            <button type="button" id="ai-assistant-clear" title="Xóa hội thoại" aria-label="Xóa hội thoại"
                class="grid h-8 w-8 place-items-center rounded-full text-white/80 hover:bg-white/15 hover:text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9-.346 9.03M9 3.75h6a2.25 2.25 0 0 1 2.25 2.25v.75H6.75V6A2.25 2.25 0 0 1 9 3.75Zm-4.5 3h15m-13.5 0 .58 12.096A2.25 2.25 0 0 0 9.325 21h5.35a2.25 2.25 0 0 0 2.245-2.154L17.5 6.75"/>
                </svg>
            </button>
        </div>

        <div id="ai-assistant-messages" class="flex max-h-96 min-h-[240px] flex-col gap-3 overflow-y-auto px-4 py-3"></div>

        <form id="ai-assistant-form" class="flex items-center gap-2 border-t border-slate-200 p-3 dark:border-slate-700">
            <input type="text" id="ai-assistant-input" autocomplete="off" maxlength="1000"
                placeholder="Nhập câu hỏi của bạn..."
                class="w-full rounded-full border border-slate-300 bg-slate-50 px-4 py-2 text-sm focus:border-primary focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <button type="submit" id="ai-assistant-send" aria-label="Gửi"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-primary text-white hover:brightness-110 disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.77 59.77 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    const CHAT_URL   = '{{ route('ai-assistant.chat') }}';
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
    const STORAGE_KEY = 'homi_ai_assistant_history';

    const widget   = document.getElementById('ai-assistant-widget');
    const toggle   = document.getElementById('ai-assistant-toggle');
    const panel    = document.getElementById('ai-assistant-panel');
    const iconOpen = document.getElementById('ai-assistant-icon-open');
    const iconClose = document.getElementById('ai-assistant-icon-close');
    const messagesEl = document.getElementById('ai-assistant-messages');
    const form     = document.getElementById('ai-assistant-form');
    const input    = document.getElementById('ai-assistant-input');
    const sendBtn  = document.getElementById('ai-assistant-send');
    const clearBtn = document.getElementById('ai-assistant-clear');

    let history = [];
    try {
        history = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
    } catch (_) {
        history = [];
    }

    function saveHistory() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history));
        } catch (_) {}
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function appendBubble(role, content) {
        const isUser = role === 'user';
        const wrapper = document.createElement('div');
        wrapper.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');
        wrapper.innerHTML = `
            <div class="max-w-[85%] rounded-2xl px-3.5 py-2 text-sm whitespace-pre-wrap ${isUser
                ? 'bg-primary text-white rounded-br-sm'
                : 'bg-slate-100 text-slate-800 rounded-bl-sm dark:bg-slate-800 dark:text-slate-100'}">${escapeHtml(content)}</div>`;
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return wrapper;
    }

    function renderAll() {
        messagesEl.innerHTML = '';
        if (history.length === 0) {
            appendBubble('assistant', 'Xin chào! Mình là trợ lý ảo của Homi Hotel. Bạn cần tìm phòng, hỏi giá hay kiểm tra phòng trống theo ngày không?');
            return;
        }
        history.forEach(turn => appendBubble(turn.role, turn.content));
    }

    function showTyping() {
        const wrapper = document.createElement('div');
        wrapper.id = 'ai-assistant-typing';
        wrapper.className = 'flex justify-start';
        wrapper.innerHTML = `
            <div class="rounded-2xl rounded-bl-sm bg-slate-100 px-3.5 py-2 text-sm text-slate-400 dark:bg-slate-800">
                Đang soạn trả lời...
            </div>`;
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        document.getElementById('ai-assistant-typing')?.remove();
    }

    function openPanel() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
        input.focus();
    }

    function closePanel() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
    }

    toggle.addEventListener('click', function () {
        panel.classList.contains('hidden') ? openPanel() : closePanel();
    });

    document.addEventListener('click', function (e) {
        if (!widget.contains(e.target)) closePanel();
    });

    clearBtn.addEventListener('click', function () {
        history = [];
        saveHistory();
        renderAll();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        input.value = '';
        sendBtn.disabled = true;

        appendBubble('user', message);
        history.push({ role: 'user', content: message });
        saveHistory();

        showTyping();

        try {
            const res = await fetch(CHAT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message, history }),
            });

            hideTyping();

            if (!res.ok) {
                appendBubble('assistant', 'Có lỗi xảy ra, vui lòng thử lại sau.');
                return;
            }

            const data = await res.json();
            const reply = data.reply || 'Xin lỗi, mình chưa có câu trả lời phù hợp.';
            appendBubble('assistant', reply);
            history.push({ role: 'assistant', content: reply });
            saveHistory();
        } catch (_) {
            hideTyping();
            appendBubble('assistant', 'Không thể kết nối tới trợ lý ảo, vui lòng thử lại sau.');
        } finally {
            sendBtn.disabled = false;
        }
    });

    renderAll();
})();
</script>
