@extends('layouts.app')

@section('title', 'Đặt đoàn/nhóm · Homi')
@section('banner_tag', 'Đặt đoàn/nhóm')
@section('banner_title', 'Đặt phòng cho đoàn/nhóm')
@section('banner_subtitle', 'Từ 5 phòng trở lên? Gửi yêu cầu để Homi liên hệ báo giá ưu đãi riêng cho đoàn/công ty của bạn.')

@section('content')
<div class="grid gap-5 md:grid-cols-[1.3fr_0.7fr]">
    <div class="card">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @guest
            <div class="alert alert-warning">
                Bạn cần <a href="{{ route('login') }}?redirect={{ urlencode(route('group-bookings.show')) }}" class="font-semibold underline">đăng nhập</a>
                hoặc <a href="{{ route('register') }}" class="font-semibold underline">đăng ký</a>
                để gửi yêu cầu đặt đoàn — nhờ đó Homi có thể liên hệ lại qua chat trực tiếp.
            </div>
        @endguest

        <form method="POST" action="{{ route('group-bookings.store') }}" class="space-y-4" @guest aria-disabled="true" @endguest>
            @csrf

            <div>
                <label class="form-label" for="company_name">Tên công ty/tổ chức (nếu có)</label>
                <input class="input" type="text" id="company_name" name="company_name" value="{{ old('company_name') }}">
            </div>

            <div>
                <label class="form-label" for="contact_name">Họ tên người liên hệ *</label>
                <input class="input" type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="group_size">Số lượng khách *</label>
                    <input class="input" type="number" id="group_size" name="group_size" min="1" value="{{ old('group_size', 5) }}" required>
                </div>
                <div>
                    <label class="form-label" for="room_count">Số phòng *</label>
                    <input class="input" type="number" id="room_count" name="room_count" min="5" value="{{ old('room_count', 5) }}" required>
                    <p id="room-count-hint" class="mt-1 hidden text-xs text-amber-600 dark:text-amber-400"></p>
                    <p id="room-count-early-hint" class="mt-1 hidden text-xs text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="num_children">Trẻ em (6-11 tuổi)</label>
                    <input class="input" type="number" id="num_children" name="num_children" min="0" value="{{ old('num_children', 0) }}">
                </div>
                <div>
                    <label class="form-label" for="num_infants">Trẻ sơ sinh (0-5 tuổi)</label>
                    <input class="input" type="number" id="num_infants" name="num_infants" min="0" value="{{ old('num_infants', 0) }}">
                </div>
            </div>
            <p id="children-bed-hint" class="-mt-2 hidden text-xs text-amber-600 dark:text-amber-400"></p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="email">Email *</label>
                    <input class="input" type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                </div>
                <div>
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input class="input" type="text" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="check_in">Ngày nhận phòng dự kiến</label>
                    <input class="input" type="date" id="check_in" name="check_in" value="{{ old('check_in') }}">
                </div>
                <div>
                    <label class="form-label" for="check_out">Ngày trả phòng dự kiến</label>
                    <input class="input" type="date" id="check_out" name="check_out" value="{{ old('check_out') }}">
                </div>
            </div>

            <div>
                <button type="button" id="suggest-options-btn" class="btn btn-outline btn-sm" disabled>Xem gợi ý phòng</button>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Điền đủ số lượng khách, ngày nhận và ngày trả để xem gợi ý.</p>
                <div id="suggest-options-loading" class="mt-2 hidden text-sm text-slate-500 dark:text-slate-400">Đang tìm phương án...</div>
                <div id="suggest-options-error" class="alert alert-warning mt-2 hidden"></div>
                <div id="suggest-options-cards" class="mt-3 grid gap-3 sm:grid-cols-3"></div>
            </div>

            <input type="hidden" name="selected_suggestion_json" id="selected_suggestion_json">

            @if ($roomTypes->isNotEmpty())
                <div>
                    <label class="form-label">Loại phòng quan tâm</label>
                    <div class="checkbox-grid">
                        @foreach ($roomTypes as $roomType)
                            <label class="checkbox-item">
                                <input type="checkbox" name="room_type_ids[]" value="{{ $roomType->id }}"
                                    @checked(in_array((string) $roomType->id, old('room_type_ids', [])))>
                                {{ $roomType->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="form-label" for="message">Ghi chú thêm</label>
                <textarea class="input" id="message" name="message" rows="4" placeholder="Mục đích chuyến đi, ngân sách dự kiến, yêu cầu đặc biệt...">{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="btn-primary w-full" @guest disabled @endguest>Gửi yêu cầu báo giá</button>
        </form>
    </div>

    <div class="h-fit space-y-5">
        <div class="card">
            <span class="section-kicker">Vì sao đặt đoàn qua form này?</span>
            <ul class="mt-3 list-disc space-y-2 pl-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                <li>Áp dụng cho đoàn/nhóm từ <strong>5 phòng trở lên</strong>.</li>
                <li>Homi sẽ liên hệ trực tiếp qua email/điện thoại để báo giá ưu đãi phù hợp quy mô đoàn.</li>
                <li>Không phải đặt phòng tự động — đây là yêu cầu tư vấn, chưa phát sinh chi phí.</li>
            </ul>

            @if ($groupPromotions->isNotEmpty())
                <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <span class="text-xs font-bold uppercase tracking-wide text-primary">Mã giảm giá đoàn/nhóm hiện có:</span>
                    <ul class="mt-2 space-y-2">
                        @foreach ($groupPromotions as $promo)
                            <li class="flex items-start gap-2 text-sm">
                                <button type="button"
                                    class="promo-copy-btn shrink-0 whitespace-nowrap rounded-md border border-dashed border-primary px-2 py-0.5 font-mono text-xs font-bold text-primary hover:bg-primary-light/40 dark:hover:bg-slate-800"
                                    data-code="{{ $promo->code }}">
                                    <span class="promo-copy-label">{{ $promo->code }} 📋</span>
                                </button>
                                <span class="text-slate-500 dark:text-slate-400">{{ $promo->description }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.promo-copy-btn').forEach((btn) => {
        const label = btn.querySelector('.promo-copy-label');
        const originalHtml = label.innerHTML;
        const code = btn.dataset.code;

        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(code);
            } catch (e) {
                return;
            }

            label.textContent = 'Đã sao chép ✓';
            btn.disabled = true;

            setTimeout(() => {
                label.innerHTML = originalHtml;
                btn.disabled = false;
            }, 1500);
        });
    });
})();
</script>

<script>
(function () {
    const childrenInput  = document.getElementById('num_children');
    const infantsInput   = document.getElementById('num_infants');
    const roomCountInput = document.getElementById('room_count');
    const hintEl          = document.getElementById('children-bed-hint');

    if (! childrenInput || ! infantsInput || ! roomCountInput || ! hintEl) return;

    // Chỉ hiển thị gợi ý tham khảo cho nhân viên — không chặn submit, không
    // đụng vào logic giường phụ thật của hệ thống đặt phòng lẻ
    // (BookingService::extraBedsNeeded()), vì form này không tách theo từng
    // loại phòng nên không thể tính đúng như logic per-room thật. Ước tính
    // đơn giản: mỗi trẻ 6-11 tuổi cần 1 giường phụ (tối đa 1 giường/phòng).
    // Riêng trẻ sơ sinh 0-5 tuổi: giới hạn 2 trẻ/phòng là LUẬT THẬT đã áp
    // dụng ở BookingService::MAX_INFANTS_PER_ROOM (đồng nhất mọi category),
    // không phải ước tính — khớp đúng số liệu server sẽ validate.
    function updateChildrenHint() {
        const children = parseInt(childrenInput.value, 10) || 0;
        const infants  = parseInt(infantsInput.value, 10) || 0;
        const rooms    = parseInt(roomCountInput.value, 10) || 0;
        const maxInfants = rooms * 2;

        const lines = [];

        if (children > 0) {
            lines.push(`Ước tính cần khoảng ${children} giường phụ cho trẻ em 6-11 tuổi (mỗi trẻ 1 giường, tối đa 1 giường/phòng).`);
            if (rooms > 0 && children > rooms) {
                lines.push(`Vượt quá ${rooms} phòng dự kiến — nhân viên sẽ tư vấn thêm phòng khi báo giá.`);
            }
        }

        if (infants > 0 && rooms > 0 && infants > maxInfants) {
            lines.push(`Trẻ sơ sinh miễn phí nhưng tối đa 2 trẻ/phòng — với ${rooms} phòng, tối đa nhận ${maxInfants} trẻ sơ sinh (đang khai ${infants}).`);
        }

        if (lines.length > 0) {
            hintEl.textContent = lines.join(' ');
            hintEl.classList.remove('hidden');
        } else {
            hintEl.classList.add('hidden');
        }
    }

    [childrenInput, infantsInput, roomCountInput].forEach((el) => {
        el.addEventListener('input', updateChildrenHint);
    });

    updateChildrenHint();
})();
</script>

<script>
(function () {
    const SUGGEST_URL = '{{ route('group-bookings.suggest-options') }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

    const groupSizeInput   = document.getElementById('group_size');
    const roomCountInput   = document.getElementById('room_count');
    const roomCountHintEl  = document.getElementById('room-count-hint');
    const earlyHintEl      = document.getElementById('room-count-early-hint');
    const checkInInput     = document.getElementById('check_in');
    const checkOutInput    = document.getElementById('check_out');
    const btn              = document.getElementById('suggest-options-btn');
    const loadingEl        = document.getElementById('suggest-options-loading');
    const errorEl          = document.getElementById('suggest-options-error');
    const cardsEl          = document.getElementById('suggest-options-cards');
    const hiddenInput      = document.getElementById('selected_suggestion_json');
    const messageEl        = document.getElementById('message');

    if (! btn) return;

    let lastAppliedRoomTypeIds = [];
    let lastAppliedNote        = '';
    let lastMinTotalRooms      = null;
    let lastNumGuests          = null;

    // Cảnh báo mềm — không chặn submit, chỉ nhắc khách "Số phòng" tự nhập có
    // thể chưa đủ so với số phòng tối thiểu cần để chứa hết "Số lượng khách"
    // (tính từ phương án ít phòng nhất trong kết quả suggest-options gần nhất).
    function updateRoomCountWarning() {
        if (lastMinTotalRooms === null) {
            roomCountHintEl.classList.add('hidden');
            return;
        }

        const currentRoomCount = parseInt(roomCountInput.value, 10) || 0;

        if (lastMinTotalRooms > currentRoomCount) {
            roomCountHintEl.textContent = `Với ${lastNumGuests} khách, cần tối thiểu khoảng ${lastMinTotalRooms} phòng (tùy loại phòng) — xem gợi ý bên dưới để điều chỉnh.`;
            roomCountHintEl.classList.remove('hidden');
        } else {
            roomCountHintEl.classList.add('hidden');
        }
    }

    function inputsValid() {
        const guests = parseInt(groupSizeInput.value, 10);
        const inVal  = checkInInput.value;
        const outVal = checkOutInput.value;

        return guests > 0 && !!inVal && !!outVal && outVal > inVal;
    }

    // Nhắc sớm (không tính số liệu, chỉ nhắc hành động) — khi khách đã điền
    // Số khách + Số phòng nhưng CHƯA điền đủ ngày nên nút "Xem gợi ý phòng"
    // còn khóa, chưa có cách nào biết chính xác cần bao nhiêu phòng (phụ
    // thuộc phòng trống thực tế theo từng ngày). Tự ẩn ngay khi đã có kết
    // quả suggest-options thật (lastMinTotalRooms !== null) để không hiện
    // chồng lên cảnh báo có số liệu thật ở updateRoomCountWarning().
    function updateRoomCountEarlyHint() {
        const guests = parseInt(groupSizeInput.value, 10) || 0;
        const rooms  = parseInt(roomCountInput.value, 10) || 0;

        if (guests > 0 && rooms > 0 && ! inputsValid() && lastMinTotalRooms === null) {
            earlyHintEl.textContent = `Điền Ngày nhận phòng và Ngày trả phòng, rồi bấm "Xem gợi ý phòng" để biết chính xác cần tối thiểu bao nhiêu phòng cho ${guests} khách.`;
            earlyHintEl.classList.remove('hidden');
        } else {
            earlyHintEl.classList.add('hidden');
        }
    }

    roomCountInput.addEventListener('input', () => {
        updateRoomCountWarning();
        updateRoomCountEarlyHint();
    });

    function syncButtonState() {
        btn.disabled = ! inputsValid();
        updateRoomCountEarlyHint();
    }

    [groupSizeInput, checkInInput, checkOutInput].forEach((el) => {
        el.addEventListener('input', syncButtonState);
        el.addEventListener('change', syncButtonState);
    });
    syncButtonState();

    function formatMoney(n) {
        return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ';
    }

    function buildNoteText(option) {
        const roomsDesc = option.rooms.map((r) => `${r.quantity} phòng ${r.room_type}`).join(', ');

        return `Khách quan tâm: ${option.label} — ${roomsDesc} (giá tạm tính: ${formatMoney(option.estimated_total_price)})`;
    }

    function applyOption(option) {
        // Bỏ tick các checkbox do lần chọn trước tự động tick, tránh chồng
        // lẫn với lựa chọn mới.
        lastAppliedRoomTypeIds.forEach((id) => {
            const cb = document.querySelector(`input[name="room_type_ids[]"][value="${id}"]`);
            if (cb) cb.checked = false;
        });

        const newIds = option.rooms.map((r) => String(r.room_type_id));
        newIds.forEach((id) => {
            const cb = document.querySelector(`input[name="room_type_ids[]"][value="${id}"]`);
            if (cb) cb.checked = true;
        });
        lastAppliedRoomTypeIds = newIds;

        const noteText = buildNoteText(option);
        const currentVal = messageEl.value;
        if (lastAppliedNote && currentVal.includes(lastAppliedNote)) {
            messageEl.value = currentVal.replace(lastAppliedNote, noteText);
        } else {
            messageEl.value = currentVal ? `${currentVal.replace(/\s+$/, '')}\n\n${noteText}` : noteText;
        }
        lastAppliedNote = noteText;

        // Khách đã chủ động chọn 1 phương án cụ thể — ghi đè thẳng "Số phòng"
        // theo phương án đó, không cần confirm thêm. Nếu sau đó khách tự sửa
        // lại tay thì để nguyên, không tự bỏ chọn hay hiện lại cảnh báo.
        roomCountInput.value = option.total_rooms;
        updateRoomCountWarning();

        hiddenInput.value = JSON.stringify(option);
    }

    function renderCards(options) {
        cardsEl.innerHTML = '';

        options.forEach((option, index) => {
            const roomsDesc = option.rooms.map((r) => `${r.quantity} phòng ${r.room_type} (${r.occupancy_each} khách/phòng)`).join('<br>');

            const card = document.createElement('label');
            card.className = 'card cursor-pointer border-2 border-transparent p-3 text-sm hover:border-blue-300';
            card.innerHTML = `
                <div class="flex items-start gap-2">
                    <input type="radio" name="suggestion_option" value="${index}" class="mt-1">
                    <div>
                        <div class="font-semibold">${option.label}</div>
                        <div class="mt-1 text-slate-500 dark:text-slate-400">${roomsDesc}</div>
                        <div class="mt-1">Tổng: ${option.total_rooms} phòng · ${option.total_capacity} khách</div>
                        <div class="font-semibold">${formatMoney(option.estimated_total_price)}</div>
                        <div class="mt-1 text-xs text-slate-400">${option.note}</div>
                    </div>
                </div>
            `;

            const radio = card.querySelector('input[type="radio"]');
            radio.addEventListener('change', () => applyOption(option));

            cardsEl.appendChild(card);
        });
    }

    btn.addEventListener('click', async () => {
        errorEl.classList.add('hidden');
        cardsEl.innerHTML = '';
        loadingEl.classList.remove('hidden');
        btn.disabled = true;
        lastMinTotalRooms = null;
        updateRoomCountWarning();
        updateRoomCountEarlyHint();

        try {
            const res = await fetch(SUGGEST_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    check_in: checkInInput.value,
                    check_out: checkOutInput.value,
                    num_guests: parseInt(groupSizeInput.value, 10),
                }),
            });

            const data = await res.json();

            if (! res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                errorEl.textContent = firstError;
                errorEl.classList.remove('hidden');
                return;
            }

            if (data.error === 'insufficient_availability') {
                errorEl.textContent = `Rất tiếc, hiện chỉ còn đủ phòng cho tối đa ${data.max_capacity_available} khách trong khoảng ngày này.`;
                errorEl.classList.remove('hidden');
                return;
            }

            if (! data.options || data.options.length === 0) {
                errorEl.textContent = 'Không tìm được phương án phù hợp, vui lòng thử khoảng ngày khác.';
                errorEl.classList.remove('hidden');
                return;
            }

            renderCards(data.options);

            lastMinTotalRooms = Math.min(...data.options.map((o) => o.total_rooms));
            lastNumGuests     = parseInt(groupSizeInput.value, 10);
            updateRoomCountWarning();
            updateRoomCountEarlyHint();
        } catch (e) {
            errorEl.textContent = 'Không thể kết nối máy chủ, vui lòng thử lại.';
            errorEl.classList.remove('hidden');
        } finally {
            loadingEl.classList.add('hidden');
            syncButtonState();
        }
    });
})();
</script>
@endsection
