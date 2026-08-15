{{-- Mã khuyến mãi dạng badge, bấm để sao chép — dùng ở bảng Khuyến mãi
     admin/staff. Script gắn listener theo kiểu delegation + cờ chặn gắn 2
     lần (window.__copyCodeBtnInit), an toàn khi partial này được include
     nhiều lần trên cùng 1 trang (VD nhiều bảng mã trên cùng trang admin). --}}
<button type="button" class="badge badge-blue copy-code-btn" data-code="{{ $code }}" title="Bấm để sao chép mã">
    <span class="copy-code-text">{{ $code }}</span>
    <span class="copy-code-icon">📋</span>
</button>

@once
@push('scripts')
<script>
if (!window.__copyCodeBtnInit) {
    window.__copyCodeBtnInit = true;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.copy-code-btn');
        if (!btn) return;

        const code = btn.dataset.code;
        const iconEl = btn.querySelector('.copy-code-icon');
        const originalIcon = iconEl.textContent;

        const showCopied = () => {
            iconEl.textContent = '✅';
            btn.title = 'Đã sao chép!';
            setTimeout(() => {
                iconEl.textContent = originalIcon;
                btn.title = 'Bấm để sao chép mã';
            }, 1500);
        };

        const fallbackCopy = () => {
            const tmp = document.createElement('textarea');
            tmp.value = code;
            tmp.style.position = 'fixed';
            tmp.style.opacity = '0';
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
            showCopied();
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(showCopied).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }
    });
}
</script>
@endpush
@endonce
