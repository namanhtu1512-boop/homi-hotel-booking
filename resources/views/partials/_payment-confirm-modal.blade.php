{{--
    Nút "Thanh toán / Hoàn tiền" dùng chung cho admin & staff — đặt cạnh "Xem
    hóa đơn" ở thanh hành động trên cùng. Tự tính mode dựa trên trạng thái
    đơn/thanh toán, không cần nơi gọi truyền amount/label thủ công:

    - collect: khách còn nợ tiền (amountDue() > 0) — modal có 2 tab "Tiền mặt"
      (mã xác nhận giả lập, đọc cho khách rồi bấm Xác nhận) và "Chuyển khoản"
      (QR VietQR thật, khách quét ngay tại quầy) — cả 2 tab đều submit chung 1
      form đánh dấu payment status = paid.
    - refund: khách sạn phải trả lại khách — hoặc đơn đang hoạt động vừa đổi
      sang phòng rẻ hơn (amountDue() < 0, vẫn set status=paid để đóng khoản
      thanh toán vì RoomChangeRequestService::approve() đã mở lại PENDING),
      hoặc đơn đã hủy mà trước đó thanh toán đủ (status=refunded). Modal chỉ
      hiện số tiền cần hoàn — không có mã/QR vì khách sạn là bên trả tiền.

    Props bắt buộc:
    - $booking: Booking hiện tại
    - $action:  URL form PATCH cập nhật trạng thái thanh toán (route
      admin|staff .bookings.update-payment)
--}}
@php
    $mode = null;
    $amount = 0.0;
    $statusValue = 'paid';
    $buttonLabel = '';

    if ($booking->payment && $booking->canMarkPaymentAsPaid()) {
        $due = $booking->amountDue();

        if ($due > 0) {
            $mode = 'collect';
            $amount = $due;
            $statusValue = 'paid';
            $buttonLabel = $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID || ((float) $booking->payment->amount_collected > 0)
                ? '💳 Xác nhận đã thu đủ số tiền còn lại'
                : '💳 Đánh dấu đã thanh toán';
        } else {
            $mode = 'refund';
            $amount = abs($due);
            $statusValue = 'paid';
            $buttonLabel = '💰 Xác nhận đã hoàn tiền cho khách';
        }
    } elseif (
        $booking->payment
        && $booking->status === \App\Enums\BookingStatus::CANCELLED
        && $booking->payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED)
    ) {
        $refundAmount = (float) ($booking->payment->amount_collected ?? 0);

        if ($refundAmount > 0) {
            $mode = 'refund';
            $amount = $refundAmount;
            $statusValue = 'refunded';
            $buttonLabel = '💰 Xác nhận đã hoàn tiền cho khách';
        }
    }

    $modalId = 'payment-modal-' . $booking->id;
    $formId = 'payment-form-' . $booking->id;
@endphp

@if ($mode)
    <form id="{{ $formId }}" method="POST" action="{{ $action }}" class="hidden">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="{{ $statusValue }}">
    </form>

    <button type="button" data-payment-modal-open="{{ $modalId }}" class="btn btn-primary btn-sm">{{ $buttonLabel }}</button>

    <div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" data-payment-modal>
        <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-primary-light text-primary dark:bg-primary/15">{{ $mode === 'refund' ? '💰' : '💳' }}</span>
                    {{ $mode === 'refund' ? 'Hoàn tiền cho khách' : 'Thanh toán' }}
                </div>
                <button type="button" data-payment-modal-close aria-label="Đóng" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">✕</button>
            </div>

            @if ($mode === 'refund')
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">Số tiền cần hoàn lại khách</p>
                <p class="mt-1 text-center text-2xl font-extrabold text-accent-dark">{{ number_format($amount, 0, ',', '.') }}đ</p>

                <p class="mt-4 rounded-lg bg-amber-50 p-2.5 text-center text-[11px] leading-relaxed text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                    Chỉ bấm Xác nhận SAU KHI đã chuyển khoản hoặc trả tiền mặt hoàn đủ số tiền trên cho khách.
                </p>

                <div class="mt-4 flex gap-2.5">
                    <button type="button" data-payment-modal-close class="btn-outline flex-1">Hủy</button>
                    <button type="submit" form="{{ $formId }}" class="btn-primary flex-1">Xác nhận đã hoàn tiền</button>
                </div>
            @else
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">Số tiền khách cần thanh toán</p>
                <p class="mt-1 text-center text-2xl font-extrabold text-primary">{{ number_format($amount, 0, ',', '.') }}đ</p>

                <div class="mt-4 flex overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <button type="button" class="flex-1 bg-primary px-3 py-1.5 text-xs font-bold text-white transition" data-payment-tab="cash">Tiền mặt</button>
                    <button type="button" class="flex-1 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-500 transition dark:bg-slate-800" data-payment-tab="transfer">Chuyển khoản</button>
                </div>

                <div data-payment-tab-panel="cash">
                    <div class="my-4 rounded-xl border-2 border-dashed border-primary/50 bg-primary-light/40 p-4 text-center dark:border-primary/40 dark:bg-primary/10">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Mã xác nhận thanh toán</p>
                        <p class="mt-1 font-mono text-3xl font-extrabold tracking-[0.3em] text-primary" data-payment-code>------</p>
                    </div>
                    <p class="rounded-lg bg-amber-50 p-2.5 text-center text-[11px] leading-relaxed text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                        Đọc mã này cho khách để xác nhận đã thanh toán đủ số tiền trên, sau đó bấm Xác nhận.
                    </p>
                </div>

                <div data-payment-tab-panel="transfer" class="hidden">
                    <div class="my-4 grid place-items-center">
                        <div class="rounded-xl border-4 border-primary p-2">
                            <img
                                src="https://img.vietqr.io/image/{{ config('services.bank_transfer.bin') }}-{{ config('services.bank_transfer.account_no') }}-qr_only.png?amount={{ (int) round($amount) }}&addInfo={{ urlencode($booking->booking_code) }}&accountName={{ urlencode(config('services.bank_transfer.account_name')) }}"
                                alt="QR chuyển khoản {{ config('services.bank_transfer.bank_name') }}"
                                class="h-[180px] w-[180px] object-contain"
                                loading="lazy"
                            >
                        </div>
                    </div>
                    <div class="space-y-1 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed dark:bg-slate-800">
                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Ngân hàng</span><strong>{{ config('services.bank_transfer.bank_name') }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Số tài khoản</span><strong class="font-mono">{{ config('services.bank_transfer.account_no') }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Nội dung CK</span><strong>{{ $booking->booking_code }}</strong></div>
                    </div>
                    <p class="mt-2 rounded-lg bg-amber-50 p-2.5 text-center text-[11px] leading-relaxed text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                        Đưa mã QR này cho khách quét để chuyển khoản, sau đó bấm Xác nhận khi đã nhận được tiền.
                    </p>
                </div>

                <div class="mt-2 flex gap-2.5">
                    <button type="button" data-payment-modal-close class="btn-outline flex-1">Hủy</button>
                    <button type="submit" form="{{ $formId }}" class="btn-primary flex-1">Xác nhận</button>
                </div>
            @endif
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                (function () {
                    function genPaymentCode() {
                        return String(Math.floor(100000 + Math.random() * 900000));
                    }

                    function closePaymentModal(modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }

                    function activateTab(modal, tabName) {
                        modal.querySelectorAll('[data-payment-tab]').forEach(function (btn) {
                            const active = btn.getAttribute('data-payment-tab') === tabName;
                            btn.classList.toggle('bg-primary', active);
                            btn.classList.toggle('text-white', active);
                            btn.classList.toggle('bg-slate-50', ! active);
                            btn.classList.toggle('text-slate-500', ! active);
                            btn.classList.toggle('dark:bg-slate-800', ! active);
                        });
                        modal.querySelectorAll('[data-payment-tab-panel]').forEach(function (panel) {
                            panel.classList.toggle('hidden', panel.getAttribute('data-payment-tab-panel') !== tabName);
                        });
                    }

                    document.addEventListener('click', function (e) {
                        const openBtn = e.target.closest('[data-payment-modal-open]');
                        if (openBtn) {
                            const modal = document.getElementById(openBtn.getAttribute('data-payment-modal-open'));
                            if (! modal) return;
                            const codeEl = modal.querySelector('[data-payment-code]');
                            if (codeEl) codeEl.textContent = genPaymentCode();
                            const firstTab = modal.querySelector('[data-payment-tab]');
                            if (firstTab) activateTab(modal, firstTab.getAttribute('data-payment-tab'));
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            return;
                        }

                        const tabBtn = e.target.closest('[data-payment-tab]');
                        if (tabBtn) {
                            const modal = tabBtn.closest('[data-payment-modal]');
                            if (modal) activateTab(modal, tabBtn.getAttribute('data-payment-tab'));
                            return;
                        }

                        const closeBtn = e.target.closest('[data-payment-modal-close]');
                        if (closeBtn) {
                            const modal = closeBtn.closest('[data-payment-modal]');
                            if (modal) closePaymentModal(modal);
                            return;
                        }

                        if (e.target.matches('[data-payment-modal]')) {
                            closePaymentModal(e.target);
                        }
                    });

                    document.addEventListener('keydown', function (e) {
                        if (e.key !== 'Escape') return;
                        document.querySelectorAll('[data-payment-modal]:not(.hidden)').forEach(closePaymentModal);
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
