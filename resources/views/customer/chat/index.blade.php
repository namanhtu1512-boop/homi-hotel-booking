@extends('layouts.app')

@section('title', 'Chat hỗ trợ · Homi')
@section('banner_tag', 'Hỗ trợ')
@section('banner_title', 'Chat với Homi')
@section('banner_subtitle', 'Nhắn tin trực tiếp cho nhân viên/admin — chúng tôi sẽ phản hồi sớm nhất.')

@section('content')
<div class="card">
    <div id="chat-thread" class="flex flex-col gap-3 overflow-y-auto" style="max-height: 55vh;">
        @forelse ($messages as $message)
            <div class="flex" style="justify-content: {{ $message->sender_id === auth()->id() ? 'flex-end' : 'flex-start' }};">
                <div class="max-w-[75%] rounded-xl px-4 py-2.5 {{ $message->sender_id === auth()->id() ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800' }}">
                    @if ($message->sender_id !== auth()->id())
                        <div class="mb-0.5 text-xs font-bold opacity-70">{{ $message->sender->name ?? 'Nhân viên' }}</div>
                    @endif
                    <div class="text-sm whitespace-pre-line">{{ $message->body }}</div>
                    <div class="mt-1 text-xs opacity-60">{{ $message->created_at->format('H:i d/m') }}</div>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                Chưa có tin nhắn nào. Gửi tin nhắn đầu tiên để bắt đầu trò chuyện với Homi.
            </p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('customer.chat.store') }}" class="mt-4 flex gap-2">
        @csrf
        <textarea name="body" class="input flex-1" rows="1" maxlength="2000" placeholder="Nhập tin nhắn..." required>{{ old('body') }}</textarea>
        <button type="submit" class="btn-primary shrink-0">Gửi</button>
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

        if (!m.is_mine) {
            const nameEl = document.createElement('div');
            nameEl.className = 'mb-0.5 text-xs font-bold opacity-70';
            nameEl.textContent = m.sender || 'Nhân viên';
            bubble.appendChild(nameEl);
        }

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
        fetch('{{ route('customer.chat.poll') }}?after=' + lastId)
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
