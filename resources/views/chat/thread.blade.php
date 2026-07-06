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
                    <div class="mt-1 text-xs opacity-60">{{ $message->created_at->format('H:i d/m') }}</div>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">Chưa có tin nhắn nào trong hội thoại này.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ $formAction }}" class="mt-4 flex gap-2">
        @csrf
        <textarea name="body" class="input flex-1" rows="1" maxlength="2000" placeholder="Trả lời khách hàng..." required>{{ old('body') }}</textarea>
        <button type="submit" class="btn btn-primary shrink-0">Gửi</button>
    </form>

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
</div>

<script>
(function () {
    const thread = document.getElementById('chat-thread');
    let lastId = {{ $messages->last()->id ?? 0 }};

    function appendMessage(m) {
        const row = document.createElement('div');
        row.className = 'flex';
        row.style.justifyContent = m.is_mine ? 'flex-end' : 'flex-start';

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] rounded-xl px-4 py-2.5 ' + (m.is_mine ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800');

        const nameEl = document.createElement('div');
        nameEl.className = 'mb-0.5 text-xs font-bold opacity-70';
        nameEl.textContent = m.sender || '—';
        bubble.appendChild(nameEl);

        const bodyEl = document.createElement('div');
        bodyEl.className = 'text-sm whitespace-pre-line';
        bodyEl.textContent = m.body;
        bubble.appendChild(bodyEl);

        const timeEl = document.createElement('div');
        timeEl.className = 'mt-1 text-xs opacity-60';
        timeEl.textContent = m.created_at;
        bubble.appendChild(timeEl);

        row.appendChild(bubble);
        thread.appendChild(row);
    }

    function poll() {
        fetch('{{ $pollRoute }}?after=' + lastId)
            .then(r => r.json())
            .then(data => {
                if (!data.messages.length) return;
                data.messages.forEach(m => {
                    appendMessage(m);
                    lastId = m.id;
                });
                thread.scrollTop = thread.scrollHeight;
            })
            .catch(() => {});
    }

    thread.scrollTop = thread.scrollHeight;
    setInterval(poll, 4000);
})();
</script>
@endsection
