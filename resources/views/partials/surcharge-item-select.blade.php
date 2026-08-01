<select class="input surcharge-item-select" style="width:auto; max-width:100%;" onchange="applySurchargeItem(this)">
    <option value="">-- Chọn vật dụng --</option>
    <option value="Khăn mặt" data-price="100000">Khăn mặt — 100.000đ</option>
    <option value="Khăn tắm" data-price="250000">Khăn tắm — 250.000đ</option>
    <option value="Áo choàng tắm" data-price="500000">Áo choàng tắm — 500.000đ</option>
    <option value="Dép đi trong phòng" data-price="50000">Dép đi trong phòng — 50.000đ</option>
    <option value="Gối" data-price="300000">Gối — 300.000đ</option>
    <option value="Ruột gối" data-price="200000">Ruột gối — 200.000đ</option>
    <option value="Chăn" data-price="600000">Chăn — 600.000đ</option>
    <option value="Ga giường" data-price="500000">Ga giường — 500.000đ</option>
    <option value="Máy sấy tóc" data-price="700000">Máy sấy tóc — 700.000đ</option>
    <option value="Ấm đun siêu tốc" data-price="500000">Ấm đun siêu tốc — 500.000đ</option>
    <option value="Điều khiển TV" data-price="300000">Điều khiển TV — 300.000đ</option>
    <option value="Ly thủy tinh" data-price="100000">Ly thủy tinh — 100.000đ</option>
    <option value="Cốc sứ" data-price="120000">Cốc sứ — 120.000đ</option>
    <option value="Bình nước" data-price="200000">Bình nước — 200.000đ</option>
    <option value="Thùng rác" data-price="250000">Thùng rác — 250.000đ</option>
    <option value="Đèn ngủ" data-price="800000">Đèn ngủ — 800.000đ</option>
    <option value="Ghế" data-price="1000000">Ghế — 1.000.000đ</option>
    <option value="TV" data-price-note="8.000.000–15.000.000đ (tùy mức độ hư hỏng)">TV — 8.000.000–15.000.000đ</option>
    <option value="Tủ lạnh mini" data-price-note="4.000.000–7.000.000đ (tùy mức độ hư hỏng)">Tủ lạnh mini — 4.000.000–7.000.000đ</option>
    <option value="Điều hòa" data-price-note="8.000.000–15.000.000đ (tùy mức độ hư hỏng)">Điều hòa — 8.000.000–15.000.000đ</option>
    <option value="">Khác (tự nhập)</option>
</select>

@once
    @push('scripts')
        <script>
            function applySurchargeItem(select) {
                const form = select.closest('form');
                const amountInput = form.querySelector('.surcharge-amount');
                const noteInput = form.querySelector('.surcharge-note');
                const opt = select.options[select.selectedIndex];
                const price = opt.getAttribute('data-price');
                const priceNote = opt.getAttribute('data-price-note');

                if (! select.value) {
                    amountInput.value = '';
                    noteInput.value = '';
                    return;
                }

                amountInput.value = price ?? '';
                noteInput.value = priceNote
                    ? `Bồi thường: ${select.value} (${priceNote})`
                    : `Bồi thường: ${select.value}`;
            }
        </script>
    @endpush
@endonce
