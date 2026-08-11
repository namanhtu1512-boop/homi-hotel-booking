@php
    $hiddenField ??= 'surcharge_item_id';
    $notePrefix ??= '';
    $itemsForJs = $items->map(function ($i) {
        return [
            'id'         => $i->id,
            'name'       => $i->name,
            'price'      => $i->price !== null ? (float) $i->price : null,
            'price_note' => $i->price_note,
        ];
    });
@endphp

<div class="surcharge-item-picker" data-items='@json($itemsForJs)' data-note-prefix="{{ $notePrefix }}" style="position:relative; width:220px; z-index:1;">
    <input type="hidden" name="{{ $hiddenField }}" class="surcharge-item-id" value="">
    <input type="text" class="input surcharge-item-search" style="width:100%;" placeholder="{{ $placeholder ?? 'Gõ để tìm...' }}" autocomplete="off">
    <div class="surcharge-item-dropdown hidden absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white text-sm shadow-lg dark:border-slate-700 dark:bg-slate-800"></div>
</div>

@once
    @push('scripts')
        <script>
            // Dùng event delegation (thay vì oninput/onfocus/onblur gắn trực tiếp
            // + hằng số/hàm global) vì trang có thể nhúng nhiều picker cùng lúc
            // (dịch vụ, hỏng/mất đồ, vi phạm, vệ sinh đặc biệt) — mỗi widget tự
            // mang theo danh sách item riêng qua data-items, không còn đụng nhau.
            (function () {
                function itemLabel(item) {
                    return item.price !== null
                        ? `${item.name} — ${Math.round(item.price).toLocaleString('vi-VN')}đ`
                        : `${item.name} — ${item.price_note ?? 'giá tùy trường hợp'}`;
                }

                function getItems(wrap) {
                    try {
                        return JSON.parse(wrap.dataset.items || '[]');
                    } catch (e) {
                        return [];
                    }
                }

                function filterItems(input) {
                    const wrap = input.closest('.surcharge-item-picker');
                    const dropdown = wrap.querySelector('.surcharge-item-dropdown');

                    // Đang gõ lại sau khi đã chọn 1 mục — coi như quay về nhập tự do
                    // ("Khác"), bỏ liên kết id cũ.
                    wrap.querySelector('.surcharge-item-id').value = '';

                    const items = getItems(wrap);
                    const q = input.value.trim().toLowerCase();
                    const matches = q ? items.filter((i) => i.name.toLowerCase().includes(q)) : items;

                    dropdown.innerHTML = matches.length
                        ? matches.map((i) => `<div class="surcharge-item-option cursor-pointer px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-700" data-id="${i.id}">${itemLabel(i)}</div>`).join('')
                        : '<div class="px-3 py-2 text-slate-400">Không tìm thấy — sẽ ghi là mục "Khác", tự nhập số tiền/lý do bên cạnh.</div>';

                    dropdown.querySelectorAll('.surcharge-item-option').forEach((el) => {
                        // mousedown (không phải click) để chạy TRƯỚC sự kiện blur của
                        // input tìm kiếm — nếu không, blur ẩn dropdown trước khi kịp
                        // click trúng.
                        el.addEventListener('mousedown', (e) => {
                            e.preventDefault();
                            selectItem(wrap, el.dataset.id);
                        });
                    });

                    dropdown.classList.remove('hidden');
                    // Trang xếp 4 picker liền kề nhau theo cột (dịch vụ/hỏng-mất/
                    // vi phạm/vệ sinh) — dropdown 1 ô có thể xổ dài đè xuống các ô
                    // bên dưới. Mỗi wrapper có z-index:1 tĩnh nên KHÔNG đủ để nằm
                    // trên các wrapper sau nó trong DOM; phải chủ động nâng z-index
                    // của wrapper đang mở lên cao nhất khi có dropdown hiển thị.
                    wrap.style.zIndex = '50';
                }

                function selectItem(wrap, id) {
                    const item = getItems(wrap).find((i) => String(i.id) === String(id));
                    if (! item) return;

                    const form = wrap.closest('form');
                    wrap.querySelector('.surcharge-item-id').value = item.id;
                    wrap.querySelector('.surcharge-item-search').value = item.name;
                    wrap.querySelector('.surcharge-item-dropdown').classList.add('hidden');
                    wrap.style.zIndex = '1';

                    const quantityInput = form.querySelector('.surcharge-quantity');
                    const quantity = parseInt(quantityInput?.value, 10) || 1;
                    const amountInput = form.querySelector('.surcharge-amount');
                    const noteInput = form.querySelector('.surcharge-note');
                    const prefix = wrap.dataset.notePrefix || '';

                    // price null (VD: TV/tủ lạnh/điều hòa, hoặc dịch vụ chưa có giá
                    // niêm yết) — để trống ô tiền, nhân viên tự nhập theo thực tế,
                    // chỉ điền sẵn ghi chú khoảng giá nếu có.
                    if (amountInput) {
                        amountInput.value = item.price !== null ? item.price * quantity : '';
                    }
                    if (noteInput) {
                        noteInput.value = item.price_note
                            ? `${prefix}${item.name} (${item.price_note})`
                            : `${prefix}${item.name}`;
                    }
                }

                function onQuantityChange(input) {
                    const form = input.closest('form');
                    const wrap = form.querySelector('.surcharge-item-picker');
                    const id = wrap?.querySelector('.surcharge-item-id').value;
                    if (! id) return;

                    const item = getItems(wrap).find((i) => String(i.id) === String(id));
                    const amountInput = form.querySelector('.surcharge-amount');
                    if (item && item.price !== null && amountInput) {
                        amountInput.value = item.price * (parseInt(input.value, 10) || 1);
                    }
                }

                document.addEventListener('input', (e) => {
                    if (e.target.matches('.surcharge-item-search')) filterItems(e.target);
                    if (e.target.matches('.surcharge-quantity')) onQuantityChange(e.target);
                });
                document.addEventListener('focus', (e) => {
                    if (e.target.matches('.surcharge-item-search')) filterItems(e.target);
                }, true);
                document.addEventListener('blur', (e) => {
                    if (e.target.matches('.surcharge-item-search')) {
                        const wrap = e.target.closest('.surcharge-item-picker');
                        // setTimeout để mousedown trên option kịp chạy trước khi dropdown ẩn.
                        setTimeout(() => {
                            wrap.querySelector('.surcharge-item-dropdown').classList.add('hidden');
                            wrap.style.zIndex = '1';
                        }, 150);
                    }
                }, true);
            })();
        </script>
    @endpush
@endonce
