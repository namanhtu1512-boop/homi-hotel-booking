@extends($layout)

@section('title', 'Chat với ' . ($customer->name ?? 'khách hàng') . ' · Homi')
@section('page_title', 'Chat với ' . ($customer->name ?? 'khách hàng'))
@section('page_subtitle', $customer->email ?? '')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ $backRoute }}" class="btn btn-outline">← Quay lại hộp thư</a>
    </div>

    <div id="chat-thread" class="flex flex-col gap-3 overflow-y-auto" style="max-height: 50vh;">
        @forelse ($messages as $message)
            @php $isStaffSide = $message->sender_id !== $customer->id; @endphp
            <div class="flex" style="justify-content: {{ $isStaffSide ? 'flex-end' : 'flex-start' }};">
                <div class="max-w-[75%] rounded-xl px-4 py-2.5 {{ $isStaffSide ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800' }}">
                    <div class="mb-0.5 text-xs font-bold opacity-70">{{ $message->sender->name ?? '—' }}</div>
                    <div class="text-sm whitespace-pre-line">{{ $message->body }}</div>
                    @if ($message->image_path)
                        <img src="{{ asset('storage/' . $message->image_path) }}" alt="ảnh" class="mt-2 max-w-[240px] rounded-lg cursor-pointer" onclick="window.open(this.src)">
                    @endif
                    <div class="mt-1 text-xs opacity-60">{{ $message->created_at->format('H:i d/m') }}</div>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">Chưa có tin nhắn nào trong hội thoại này.</p>
        @endforelse
    </div>

    <form id="chat-form" class="mt-4 space-y-2" enctype="multipart/form-data">
        @csrf
        <div class="flex gap-2">
            <textarea id="chat-body" name="body" class="input flex-1" rows="1" maxlength="2000" placeholder="Trả lời khách hàng..."></textarea>
            <label class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-800" title="Đính kèm ảnh">
                📎
                <input type="file" name="image" accept="image/*" class="hidden" onchange="previewImg(this)">
            </label>
            <button type="submit" class="btn btn-primary shrink-0">Gửi</button>
        </div>
        <div id="img-preview" class="hidden">
            <img id="img-preview-src" class="h-20 rounded-lg border border-slate-200" alt="preview">
            <button type="button" onclick="clearImg()" class="ml-2 text-xs text-red-500 hover:underline">Xóa ảnh</button>
        </div>
        <div id="chat-error" class="hidden text-sm text-red-500"></div>
    </form>
</div>

<script>
function previewImg(input) {
    const preview = document.getElementById('img-preview');
    const src     = document.getElementById('img-preview-src');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { src.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}
function clearImg() {
    document.getElementById('img-preview').classList.add('hidden');
    document.getElementById('img-preview-src').src = '';
    const fileInput = document.querySelector('#chat-form input[type="file"]');
    if (fileInput) fileInput.value = '';
}

(function () {
    const thread   = document.getElementById('chat-thread');
    const form     = document.getElementById('chat-form');
    const bodyEl   = document.getElementById('chat-body');
    const errEl    = document.getElementById('chat-error');
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content;
    let lastId     = {{ $messages->last()->id ?? 0 }};
    let sending    = false;

    function buildBubble(m) {
        const row = document.createElement('div');
        row.className = 'flex';
        row.style.justifyContent = m.is_mine ? 'flex-end' : 'flex-start';
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] rounded-xl px-4 py-2.5 ' + (m.is_mine ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800');
        const nameEl = document.createElement('div');
        nameEl.className = 'mb-0.5 text-xs font-bold opacity-70';
        nameEl.textContent = m.sender || '—';
        bubble.appendChild(nameEl);
        if (m.body) {
            const b = document.createElement('div');
            b.className = 'text-sm whitespace-pre-line';
            b.textContent = m.body;
            bubble.appendChild(b);
        }
        if (m.image_url) {
            const img = document.createElement('img');
            img.src = m.image_url;
            img.className = 'mt-2 max-w-[240px] rounded-lg cursor-pointer';
            img.onclick = () => window.open(m.image_url);
            bubble.appendChild(img);
        }
        const t = document.createElement('div');
        t.className = 'mt-1 text-xs opacity-60';
        t.textContent = m.created_at;
        bubble.appendChild(t);
        row.appendChild(bubble);
        return row;
    }

    function appendMessage(m) {
        thread.appendChild(buildBubble(m));
        thread.scrollTop = thread.scrollHeight;
    }

    function poll() {
        fetch('{{ $pollRoute }}?after=' + lastId)
            .then(r => r.json())
            .then(data => {
                if (!data.messages?.length) return;
                data.messages.forEach(m => { appendMessage(m); lastId = m.id; });
            })
            .catch(() => {});
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (sending) return;
        sending = true;
        errEl.classList.add('hidden');

        const fd = new FormData(form);
        fd.append('_token', CSRF);

        try {
            const res  = await fetch('{{ $formAction }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok) { errEl.textContent = data.error ?? 'Lỗi gửi tin.'; errEl.classList.remove('hidden'); return; }
            appendMessage(data);
            lastId = data.id;
            bodyEl.value = '';
            clearImg();
        } catch { errEl.textContent = 'Lỗi kết nối.'; errEl.classList.remove('hidden'); }
        finally { sending = false; }
    });

    bodyEl.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });

    thread.scrollTop = thread.scrollHeight;
    setInterval(poll, 4000);
})();
</script>
@endsection
