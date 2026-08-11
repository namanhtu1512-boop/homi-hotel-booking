<?php

namespace Database\Seeders;

use App\Enums\SurchargeCategory;
use App\Models\SurchargeItem;
use Illuminate\Database\Seeder;

class SurchargeItemSeeder extends Seeder
{
    /**
     * Dữ liệu chuyển thể từ danh sách hardcode cũ trong
     * resources/views/partials/surcharge-item-select.blade.php.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Khăn mặt', 'price' => 100000],
            ['name' => 'Khăn tắm', 'price' => 250000],
            ['name' => 'Áo choàng tắm', 'price' => 500000],
            ['name' => 'Dép đi trong phòng', 'price' => 50000],
            ['name' => 'Gối', 'price' => 300000],
            ['name' => 'Ruột gối', 'price' => 200000],
            ['name' => 'Chăn', 'price' => 600000],
            ['name' => 'Ga giường', 'price' => 500000],
            ['name' => 'Máy sấy tóc', 'price' => 700000],
            ['name' => 'Ấm đun siêu tốc', 'price' => 500000],
            ['name' => 'Điều khiển TV', 'price' => 300000],
            ['name' => 'Ly thủy tinh', 'price' => 100000],
            ['name' => 'Cốc sứ', 'price' => 120000],
            ['name' => 'Bình nước', 'price' => 200000],
            ['name' => 'Thùng rác', 'price' => 250000],
            ['name' => 'Đèn ngủ', 'price' => 800000],
            ['name' => 'Ghế', 'price' => 1000000],
            ['name' => 'TV', 'price' => null, 'price_note' => '8.000.000–15.000.000đ (tùy mức độ hư hỏng)'],
            ['name' => 'Tủ lạnh mini', 'price' => null, 'price_note' => '4.000.000–7.000.000đ (tùy mức độ hư hỏng)'],
            ['name' => 'Điều hòa', 'price' => null, 'price_note' => '8.000.000–15.000.000đ (tùy mức độ hư hỏng)'],
        ];

        foreach ($items as $item) {
            SurchargeItem::firstOrCreate(
                ['name' => $item['name']],
                $item + ['status' => 'active', 'category' => SurchargeCategory::Damage]
            );
        }

        // Đổi tên vài dòng đã seed trước đó (11/08) cho khớp bảng giá chính
        // thức mới — chạy TRƯỚC vòng lặp bên dưới, vô hại trên DB mới tinh.
        $renames = [
            'Hút thuốc khu vực cấm' => 'Hút thuốc tại khu vực cấm',
            'Gây ồn quá mức'        => 'Gây ồn nghiêm trọng',
            'Làm bẩn ga'            => 'Làm bẩn ga giường',
            'Đổ nước/đồ uống'       => 'Đổ đồ uống lên giường',
            'Nôn ói'                => 'Nôn ói trong phòng',
            'Bùn đất'               => 'Bùn đất nghiêm trọng',
            'Mùi thức ăn'           => 'Mùi thức ăn nghiêm trọng',
        ];

        foreach ($renames as $oldName => $newName) {
            SurchargeItem::where('name', $oldName)->update(['name' => $newName]);
        }

        // Danh mục đầy đủ cho 3 form phụ phí (hỏng/mất đồ, vi phạm, vệ sinh
        // đặc biệt) theo đặc tả 4-form của Homi, giá thật theo bảng giá Homi
        // cung cấp — KHÔNG bao gồm hủy phòng, khách thêm, phí thanh toán
        // (những khoản đó hệ thống tự tính, không để nhân viên tìm tay).
        // price = null: chưa có giá thật, nhân viên tự nhập theo thực tế.
        $damageGroups = [
            'Đồ giường' => [
                'Gối' => 300000, 'Vỏ gối' => 100000, 'Chăn' => 600000, 'Vỏ chăn' => 300000,
                'Ga giường' => 400000, 'Tấm bảo vệ nệm' => 300000, 'Nệm' => 3000000,
                'Khăn trải giường' => null,
            ],
            'Phòng tắm' => [
                'Khăn tắm' => 250000, 'Khăn mặt' => 100000, 'Khăn tay' => 80000, 'Dép' => 50000,
                'Áo choàng tắm' => 500000, 'Máy sấy tóc' => 500000, 'Gương' => 500000,
                'Vòi sen' => 800000, 'Dây sen' => 200000, 'Nắp bồn cầu' => 700000, 'Lavabo' => 2000000,
                'Vòi nước' => 500000,
                'Áo choàng' => null, 'Giá khăn' => null, 'Kệ phòng tắm' => null,
            ],
            'Điện tử' => [
                'Remote TV' => 300000, 'Điều hòa' => 8000000, 'Remote điều hòa' => 300000,
                'Điện thoại bàn' => 500000, 'Ấm đun nước' => 500000, 'Máy sấy tóc' => 500000,
                'Đèn bàn' => 300000, 'Đèn ngủ' => 400000, 'Ổ cắm' => 200000, 'Adapter' => 300000,
                'Bộ sạc' => 300000,
                'USB charger' => null, 'Đèn trần' => null,
                // TV xử lý riêng bên dưới (bỏ price_note cũ vì đã có giá cố định).
            ],
            'Nội thất' => [
                'Bàn' => 1000000, 'Ghế' => 1000000, 'Sofa' => 3000000, 'Tủ' => 3000000,
                'Két sắt' => 2000000, 'Giường' => 5000000, 'Rèm' => 1000000, 'Móc quần áo' => 50000,
                'Gương' => 500000, 'Tranh' => 300000, 'Đồ trang trí' => 200000,
            ],
            'Đồ dùng phòng' => [
                'Thẻ phòng' => 200000, 'Chìa khóa' => 300000, 'Thẻ gửi xe' => 100000,
                'Bảng "Do Not Disturb"' => 100000, 'Bảng hướng dẫn phòng' => 100000,
                'Remote' => null,
            ],
            'Đồ dùng ăn uống' => [
                'Ly' => 100000, 'Cốc' => 80000, 'Tách' => 100000, 'Muỗng' => 50000, 'Dao' => 100000,
                'Nĩa' => 100000, 'Khay' => 200000, 'Bình nước' => 200000,
            ],
        ];

        foreach ($damageGroups as $group => $names) {
            foreach ($names as $name => $price) {
                SurchargeItem::updateOrCreate(['name' => $name], [
                    'price'    => $price,
                    'category' => SurchargeCategory::Damage,
                    'group'    => $group,
                    'status'   => 'active',
                ]);
            }
        }

        // TV: giá dao động 8-15tr trước đây → nay có giá cố định thật, bỏ
        // price_note cũ (updateOrCreate ở trên không đụng cột chưa liệt kê,
        // phải set thẳng ở đây mới xóa được ghi chú cũ).
        SurchargeItem::where('name', 'TV')->update([
            'price' => 5000000, 'price_note' => null, 'category' => SurchargeCategory::Damage, 'group' => 'Điện tử',
        ]);
        SurchargeItem::where('name', 'Điều hòa')->update(['price_note' => null]);

        $violationItems = [
            'Hút thuốc trong phòng'         => 1000000,
            'Hút thuốc tại khu vực cấm'     => 500000,
            'Mang thú cưng trái quy định'   => 500000,
            'Vượt số người quy định'        => 300000,
            'Tổ chức tiệc trong phòng'      => 1000000,
            'Gây ồn nghiêm trọng'           => 500000,
            'Sử dụng phòng sai mục đích'    => 1000000,
            'Mang vật bị cấm vào phòng'     => 1000000,
            'Đỗ xe sai quy định'            => 200000,
            'Không trả thẻ/chìa khóa'       => 200000,
        ];

        foreach ($violationItems as $name => $price) {
            SurchargeItem::updateOrCreate(['name' => $name], [
                'price'    => $price,
                'category' => SurchargeCategory::Violation,
                'group'    => 'Vi phạm quy định',
                'status'   => 'active',
            ]);
        }

        $cleaningGroups = [
            'Đồ vải' => [
                'Làm bẩn ga giường' => 200000, 'Làm bẩn chăn' => 300000, 'Làm bẩn vỏ gối' => 100000,
                'Làm bẩn nệm' => 500000, 'Làm bẩn khăn tắm' => 150000, 'Làm bẩn khăn mặt' => 100000,
            ],
            'Nội thất' => [
                'Làm bẩn sofa' => 500000, 'Làm bẩn thảm' => 300000, 'Làm bẩn rèm' => 300000,
                'Làm bẩn ghế' => 200000,
            ],
            'Vệ sinh đặc biệt' => [
                'Phòng quá bẩn' => 300000, 'Đổ thức ăn lên giường' => 300000,
                'Đổ đồ uống lên giường' => 200000, 'Nôn ói trong phòng' => 500000,
                'Bùn đất nghiêm trọng' => 300000, 'Trang điểm làm bẩn khăn' => 150000,
                'Mùi thuốc lá' => 500000, 'Mùi thức ăn nghiêm trọng' => 300000,
                'Rác bất thường' => 200000, 'Vệ sinh đặc biệt sau check-out' => 500000,
                'Máu' => null,
            ],
        ];

        foreach ($cleaningGroups as $group => $names) {
            foreach ($names as $name => $price) {
                SurchargeItem::updateOrCreate(['name' => $name], [
                    'price'    => $price,
                    'category' => SurchargeCategory::Cleaning,
                    'group'    => $group,
                    'status'   => 'active',
                ]);
            }
        }
    }
}
