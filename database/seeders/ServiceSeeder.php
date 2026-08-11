<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // 2 dịch vụ giá cố định gốc không nằm trong bất kỳ bảng giá mới nào
        // (giữ nguyên tên/giá — có thể đã được nhân viên dùng thật trong
        // booking, đừng đổi tên).
        Service::updateOrCreate(['name' => 'Ăn sáng buffet'], [
            'description' => 'Buffet sáng tại nhà hàng khách sạn, phục vụ 6:00 - 10:00.',
            'price'       => 150000,
            'status'      => 'active',
        ]);
        Service::updateOrCreate(['name' => 'Trả phòng muộn (đến 18:00)'], [
            'description' => 'Giữ phòng đến 18:00 thay vì giờ trả phòng tiêu chuẩn.',
            'price'       => 200000,
            'status'      => 'active',
        ]);

        // Đổi tên vài dòng đã seed trước đó (11/08) cho khớp bảng giá chính
        // thức mới — chạy TRƯỚC vòng lặp bên dưới để updateOrCreate() theo
        // tên mới trúng đúng bản ghi cũ, không tạo trùng. Vô hại trên DB mới
        // tinh (tên cũ chưa từng tồn tại thì đơn giản không làm gì).
        $renames = [
            'Ăn sáng buffet trẻ em' => 'Ăn sáng buffet trẻ em 6–11 tuổi',
            'Ăn theo đoàn'          => 'Suất ăn đoàn',
            'Xe 4 chỗ'              => 'Thuê xe 4 chỗ',
            'Xe 7 chỗ'              => 'Thuê xe 7 chỗ',
            'Xe 16 chỗ'             => 'Thuê xe 16 chỗ',
            'Đưa đón sân bay'       => 'Đưa đón sân bay 4 chỗ',
            'Thuê xe theo giờ'      => 'Thuê xe theo giờ 4 chỗ',
            'Thêm bàn là'           => 'Thuê bàn là',
            'Thêm máy sấy'          => 'Thuê máy sấy',
            'Giặt áo'               => 'Giặt áo sơ mi',
            'Trang trí sinh nhật'   => 'Trang trí sinh nhật cơ bản',
            'Thuê phòng họp'        => 'Phòng họp',
            'Thuê phòng hội nghị'   => 'Phòng hội nghị',
        ];

        foreach ($renames as $oldName => $newName) {
            Service::where('name', $oldName)->update(['name' => $newName]);
        }

        // Danh mục "Dịch vụ phát sinh" đầy đủ theo phân nhóm, giá thật theo
        // bảng giá Homi cung cấp — price = null cho các mục CHƯA có giá thật,
        // nhân viên tự nhập số tiền khi thêm cho booking (xem
        // BookingService::addServiceItem()). KHÔNG gồm nhóm "Khách thêm"
        // (người lớn/trẻ em/giường phụ thêm...) hay % phí Early/Late
        // check-in/out — check-in/out tính theo % TIỀN PHÒNG THỰC TẾ của
        // từng đơn (không phải giá cố định), cần 1 tính năng tính tự động
        // riêng, không hợp với catalog phẳng này — KHÔNG tự bịa cách quy đổi.
        $groups = [
            'Ăn uống' => [
                'Ăn sáng buffet người lớn'          => 150000,
                'Ăn sáng buffet trẻ em 6–11 tuổi'    => 75000,
                'Ăn sáng mang đi'                    => 100000,
                'Ăn trưa'                             => 180000,
                'Ăn tối'                              => 220000,
                'Set menu'                            => 250000,
                'Suất ăn đoàn'                        => 180000,
                'Room service'                        => 50000,
                'Đồ ăn nhẹ'                            => 50000,
                'Trái cây'                             => 100000,
                'Bánh ngọt'                            => 60000,
                'Bánh sinh nhật'                       => 300000,
                'Hoa quả chào mừng'                    => 150000,
                'Cà phê'                               => 40000,
                'Trà'                                  => 30000,
                // Chưa có giá riêng ngoài bảng Minibar bên dưới (đè giá lại).
                'Nước suối' => null, 'Nước ngọt' => null, 'Bia' => null,
                'Rượu vang' => null, 'Champagne' => null,
            ],
            // Cùng tên với 1 số món ở "Ăn uống" (Nước suối/Cà phê/Trà...) —
            // xử lý sau (group Minibar), giá/nhóm Minibar được ưu tiên vì
            // seed sau ghi đè seed trước, coi minibar là ngữ cảnh cụ thể hơn.
            'Minibar' => [
                'Nước suối' => 20000, 'Nước ngọt' => 30000, 'Bia' => 50000,
                'Nước trái cây' => 40000, 'Snack' => 30000, 'Chocolate' => 50000,
                'Kẹo' => 20000, 'Cà phê' => 30000, 'Trà' => 20000,
            ],
            'Vận chuyển' => [
                'Đưa đón sân bay 4 chỗ'   => 300000,
                'Đưa đón sân bay 7 chỗ'   => 400000,
                'Đón/tiễn ga tàu'          => 200000,
                'Đón/tiễn bến xe'          => 200000,
                'Thuê xe 4 chỗ'            => 800000,
                'Thuê xe 7 chỗ'            => 1000000,
                'Thuê xe 16 chỗ'           => 1500000,
                'Thuê xe theo giờ 4 chỗ'   => 150000,
                'Thuê xe máy'              => 150000,
                'Thuê xe đạp'              => 80000,
                'Phí đỗ xe'                => 50000,
                // Mục cũ chưa có giá mới (đã gộp vào các mục trên) — vẫn giữ
                // để nhân viên tự nhập tiền nếu cần dùng riêng.
                'Tiễn sân bay' => null, 'Đón ga tàu' => null, 'Tiễn ga tàu' => null,
                'Đón bến xe' => null, 'Tiễn bến xe' => null, 'Thuê xe theo ngày' => null,
                'Thuê xe tham quan' => null, 'Thuê xe đoàn' => null, 'Taxi' => null,
            ],
            'Phòng & tiện nghi' => [
                'Giường phụ'          => 250000,
                'Nôi em bé'            => 0, // Miễn phí
                'Cũi em bé'            => 0, // Miễn phí
                'Thêm chăn'            => 50000,
                'Thêm gối'             => 30000,
                'Thêm khăn'            => 30000,
                'Thêm dép'             => 20000,
                'Thuê bàn là'          => 50000,
                'Thuê máy sấy'         => 50000,
                'Thuê adapter'         => 50000,
                'Thuê bộ sạc'          => 50000,
                'Thuê ô'               => 30000,
                'Thêm móc quần áo' => null, 'Thêm ấm đun nước' => null, 'Thêm đồ dùng cá nhân' => null,
            ],
            'Vệ sinh' => [
                'Dọn phòng thêm' => null, 'Dọn phòng ngoài giờ' => null, 'Thay ga giường thêm' => null,
                'Thay chăn thêm' => null, 'Thay vỏ gối thêm' => null, 'Thay khăn thêm' => null,
                'Vệ sinh sofa' => null, 'Vệ sinh nệm' => null, 'Vệ sinh thảm' => null, 'Vệ sinh rèm' => null,
            ],
            'Giặt là' => [
                'Giặt áo sơ mi'    => 50000,
                'Giặt quần'        => 50000,
                'Giặt váy'         => 70000,
                'Giặt áo khoác'    => 100000,
                'Giặt vest'        => 150000,
                'Giặt đồ trẻ em'   => 40000,
                'Giặt đồ bơi'      => 50000,
                'Giặt giày'        => 100000,
                'Giặt túi'         => 100000,
                'Giặt chăn'        => 150000,
                'Giặt ga'          => 100000,
                'Giặt khăn'        => 30000,
                'Sấy quần áo'      => 50000,
                'Ủi áo'            => 30000,
                'Ủi quần'          => 30000,
                'Ủi vest'          => 80000,
                'Giặt đồ lót' => null, 'Giặt trong ngày' => null,
                // Giặt nhanh/khẩn cấp là PHỤ THU % (không phải giá cố định) —
                // set riêng bên dưới kèm price_note, không đặt số ở đây.
            ],
            // Early/Late check-in, Late check-out: phí tính theo % tiền
            // phòng thực tế + mốc giờ (VD "trước 06:00: 100%", "12:00-14:00:
            // miễn phí nếu còn phòng") — KHÔNG thể quy về 1 giá cố định
            // trong catalog này, cần 1 tính năng tính tự động riêng theo
            // chính sách (đặc tả 4-form của Homi cũng khuyến nghị vậy).
            // "Gia hạn thêm 1 đêm" đã có luồng riêng (extend-stay).
            'Check-in / Check-out' => [
                'Gia hạn thêm giờ' => null,
            ],
            'Trang trí & sự kiện' => [
                'Trang trí sinh nhật cơ bản'  => 300000,
                'Trang trí sinh nhật cao cấp' => 600000,
                'Trang trí honeymoon'          => 500000,
                'Trang trí kỷ niệm'            => 400000,
                'Trang trí cầu hôn'            => 1000000,
                'Hoa tươi'                      => 300000,
                'Bóng bay'                      => 150000,
                'Banner'                        => 100000,
                'Bánh sinh nhật'                => 300000,
                'Champagne'                     => 500000,
                'Quà chào mừng'                 => 200000,
                'Trang trí phòng' => null, 'Thiệp' => null, 'Setup bàn ăn' => null, 'Setup tiệc' => null,
            ],
            'Spa & giải trí' => [
                'Massage' => null, 'Massage chân' => null, 'Massage body' => null, 'Facial' => null,
                'Chăm sóc da' => null, 'Sauna' => null, 'Steam bath' => null, 'Jacuzzi' => null,
                'Beauty salon' => null, 'Gym trainer' => null, 'Yoga' => null, 'Tour' => null,
                'Hướng dẫn viên' => null, 'Đặt vé tham quan' => null,
            ],
            'Tiện ích khác' => [
                'Giữ hành lý' => null, 'Giao hành lý' => null, 'Gửi hành lý trước check-in' => null,
                'Gửi hành lý sau check-out' => null, 'In' => null, 'Photocopy' => null, 'Scan' => null,
                'Fax' => null, 'Điện thoại' => null, 'Gọi quốc tế' => null,
                'Thuê xe đẩy trẻ em' => null, 'Thuê ghế ăn trẻ em' => null,
            ],
            'Đoàn / Hội nghị' => [
                'Phòng họp'          => 1000000,
                'Phòng hội nghị'     => 2000000,
                'Máy chiếu'          => 300000,
                'Màn chiếu'          => 200000,
                'Micro'              => 100000,
                'Loa'                => 300000,
                'Flipchart'          => 100000,
                'Teabreak'           => 80000,
                'Coffee break'       => 100000,
                'Setup hội nghị'     => 500000,
                'Nhân viên hỗ trợ'   => 300000,
                'Thuê phòng sự kiện' => null, 'Bảng trắng' => null, 'Bút' => null,
                'Ăn trưa đoàn' => null, 'Ăn tối đoàn' => null, 'Trang trí hội nghị' => null,
                'Xe đưa đón đoàn' => null,
            ],
        ];

        foreach ($groups as $group => $items) {
            foreach ($items as $name => $price) {
                Service::updateOrCreate(
                    ['name' => $name],
                    ['price' => $price, 'group' => $group, 'status' => 'active']
                );
            }
        }

        // Phụ thu theo %, không phải giá cố định.
        Service::updateOrCreate(['name' => 'Giặt nhanh'], [
            'price' => null, 'price_note' => '+50% giá dịch vụ giặt tương ứng',
            'group' => 'Giặt là', 'status' => 'active',
        ]);
        Service::updateOrCreate(['name' => 'Giặt khẩn cấp'], [
            'price' => null, 'price_note' => '+100% giá dịch vụ giặt tương ứng',
            'group' => 'Giặt là', 'status' => 'active',
        ]);
    }
}
