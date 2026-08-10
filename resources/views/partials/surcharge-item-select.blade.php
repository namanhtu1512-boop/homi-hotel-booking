<div class="surcharge-item-picker" style="position:relative; width:220px;">
    <input type="hidden" name="surcharge_item_id" class="surcharge-item-id" value="">
    <input type="text" class="input surcharge-item-search" style="width:100%;" placeholder="Gõ để tìm vật dụng..."
           autocomplete="off" oninput="filterSurchargeItems(this)" onfocus="filterSurchargeItems(this)" onblur="hideSurchargeDropdown(this)">
    <div class="surcharge-item-dropdown hidden absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white text-sm shadow-lg dark:border-slate-700 dark:bg-slate-800"></div>
</div>

@php
    $surchargeItemsForJs = $surchargeItems->map(function ($i) {
        return [
            'id'         => $i->id,
            'name'       => $i->name,
            'price'      => $i->price !== null ? (float) $i->price : null,
            'price_note' => $i->price_note,
        ];
    });
@endphp

@once
    @push('scripts')
        <script>
            const SURCHARGE_ITEMS = @json($surchargeItemsForJs);

            function surchargeItemLabel(item) {
                return item.price !== null
                    ? `${item.name} — ${Math.round(item.price).toLocaleString('vi-VN')}đ`
                    : `${item.name} — ${item.price_note ?? 'giá tùy trường hợp'}`;
            }

            function filterSurchargeItems(input) {
                const wrap = input.closest('.surcharge-item-picker');
                const dropdown = wrap.querySelector('.surcharge-item-dropdown');

                // Đang gõ lại sau khi đã chọn 1 mục — coi như quay về nhập tự do
                // ("Khác"), bỏ liên kết surcharge_item_id cũ.
                wrap.querySelector('.surcharge-item-id').value = '';

                const q = input.value.trim().toLowerCase();
                const matches = q ? SURCHARGE_ITEMS.filter((i) => i.name.toLowerCase().includes(q)) : SURCHARGE_ITEMS;

                dropdown.innerHTML = matches.length
                    ? matches.map((i) => `<div class="surcharge-item-option cursor-pointer px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-700" data-id="${i.id}">${surchargeItemLabel(i)}</div>`).join('')
                    : '<div class="px-3 py-2 text-slate-400">Không tìm thấy — sẽ ghi là mục "Khác", tự nhập số tiền/lý do bên cạnh.</div>';

                dropdown.querySelectorAll('.surcharge-item-option').forEach((el) => {
                    // mousedown (không phải click) để chạy TRƯỚC sự kiện blur của
                    // input tìm kiếm — nếu không, blur ẩn dropdown trước khi kịp
                    // click trúng.
                    el.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectSurchargeItem(wrap, el.dataset.id);
                    });
                });

                dropdown.classList.remove('hidden');
            }

            function selectSurchargeItem(wrap, id) {
                const item = SURCHARGE_ITEMS.find((i) => String(i.id) === String(id));
                if (! item) return;

                const form = wrap.closest('form');
                wrap.querySelector('.surcharge-item-id').value = item.id;
                wrap.querySelector('.surcharge-item-search').value = item.name;
                wrap.querySelector('.surcharge-item-dropdown').classList.add('hidden');

                const quantityInput = form.querySelector('.surcharge-quantity');
                const quantity = parseInt(quantityInput?.value, 10) || 1;
                const amountInput = form.querySelector('.surcharge-amount');
                const noteInput = form.querySelector('.surcharge-note');

                // price null (VD: TV/tủ lạnh/điều hòa) — để trống ô tiền, nhân viên
                // tự nhập theo thực tế hư hỏng, chỉ điền sẵn ghi chú khoảng giá.
                amountInput.value = item.price !== null ? item.price * quantity : '';
                noteInput.value = item.price_note
                    ? `Bồi thường: ${item.name} (${item.price_note})`
                    : `Bồi thường: ${item.name}`;
            }

            function hideSurchargeDropdown(input) {
                // setTimeout để mousedown trên option kịp chạy trước khi dropdown ẩn.
                setTimeout(() => input.closest('.surcharge-item-picker').querySelector('.surcharge-item-dropdown').classList.add('hidden'), 150);
            }

            function onSurchargeQuantityChange(input) {
                const form = input.closest('form');
                const id = form.querySelector('.surcharge-item-id').value;
                if (! id) return;

                const item = SURCHARGE_ITEMS.find((i) => String(i.id) === String(id));
                if (item && item.price !== null) {
                    form.querySelector('.surcharge-amount').value = item.price * (parseInt(input.value, 10) || 1);
                }
            }
        </script>
    @endpush
@endonce
