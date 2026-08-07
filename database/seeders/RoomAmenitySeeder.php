<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * Seed tiện nghi TRONG PHÒNG theo từng loại phòng (khác với tiện nghi cấp
 * khách sạn của HotelInfoSeeder — hồ bơi/gym/nhà hàng... dùng chung cho mọi
 * phòng). Cấu trúc cộng dồn theo hạng: Standard là nền tảng; Superior =
 * Standard + thêm; Deluxe = Superior + thêm; Family và Suite đều cộng thêm
 * riêng trên nền Deluxe (không cộng dồn qua nhau) — đúng theo bảng tiện nghi
 * người dùng cung cấp.
 *
 * Nhờ cấu trúc cộng dồn này, RoomTypeService::amenityTiers() có thể tự tính
 * tiện nghi nào "độc quyền/nâng cấp" dựa trên số phòng cùng sở hữu, không
 * cần hard-code theo tên phòng ở đây.
 */
class RoomAmenitySeeder extends Seeder
{
    public function run(): void
    {
        $standard = [
            'Máy điều hòa'                              => '❄️',
            'TV Smart 43 inch'                          => '📺',
            'Wifi tốc độ cao miễn phí'                  => '📶',
            'Tủ lạnh mini'                               => '🧊',
            'Két sắt điện tử'                            => '🔒',
            'Ấm đun nước'                                => '♨️',
            'Máy sấy tóc'                                => '💨',
            'Bàn làm việc'                               => '🖊️',
            'Ghế đọc sách'                               => '📖',
            'Tủ quần áo'                                 => '👕',
            'Điện thoại nội bộ'                          => '☎️',
            'Phòng tắm đứng'                             => '🚿',
            'Dép đi trong phòng'                         => '🩴',
            'Bộ bàn chải, dầu gội, sữa tắm'              => '🧴',
            'Nước uống miễn phí (2 chai/ngày)'           => '💧',
            'Trà và cà phê miễn phí'                     => '🍵',
        ];

        $superiorAdds = [
            'Cửa sổ lớn'                    => '🪟',
            'View thành phố'                => '🌆',
            'Ghế sofa nhỏ'                   => '🛋️',
            'Bàn trà'                        => '🫖',
            'Áo choàng tắm'                  => '🧥',
            'Bộ ly thủy tinh cao cấp'        => '🥂',
            'Gối ngủ cao cấp'                => '☁️',
        ];

        $deluxeAdds = [
            'Ban công riêng'                     => '🌇',
            'View biển hoặc view thành phố đẹp'  => '🌊',
            'TV Smart 55 inch'                   => '📺',
            'Máy pha cà phê'                      => '☕',
            'Minibar'                             => '🍹',
            'Gương trang điểm'                    => '🪞',
            'Bồn tắm'                             => '🛁',
            'Bộ khăn tắm cao cấp'                 => '🧺',
            'Loa Bluetooth'                       => '🔊',
        ];

        // Family cộng thêm trên nền Deluxe (đã bỏ "TV Smart 55 inch" vì trùng với Deluxe).
        $familyAdds = [
            'Không gian sinh hoạt rộng'   => '🛋️',
            'Sofa lớn'                     => '🛋️',
            'Bàn ăn nhỏ'                    => '🍽️',
            'Lò vi sóng'                    => '🍲',
            'Bộ chén đĩa'                   => '🥣',
            'Ghế trẻ em (theo yêu cầu)'    => '🧒',
            '02 tủ quần áo'                 => '👕',
            'Phòng tắm rộng'                => '🛁',
        ];

        // Suite cộng thêm riêng trên nền Deluxe (không cộng dồn qua Family).
        $suiteAdds = [
            'Phòng khách riêng'                => '🛋️',
            'Ban công lớn'                      => '🌇',
            'View đẹp nhất khách sạn'           => '🌅',
            'Sofa cao cấp'                      => '🛋️',
            'Bàn ăn'                            => '🍽️',
            'Máy pha cà phê viên nén'           => '☕',
            'Minibar đầy đủ'                    => '🍹',
            'Bồn tắm nằm'                       => '🛁',
            'Vòi sen mưa'                       => '🌧️',
            'Smart TV 65 inch'                  => '📺',
            'Hệ thống âm thanh Bluetooth'       => '🔊',
            'Bàn làm việc riêng'                => '🖊️',
            'Két sắt cỡ lớn'                    => '🔒',
            'Gương toàn thân'                   => '🪞',
            'Áo choàng tắm cao cấp'             => '🧥',
            'Máy sấy tóc công suất lớn'         => '💨',
        ];

        $catalog = $standard + $superiorAdds + $deluxeAdds + $familyAdds + $suiteAdds;

        foreach ($catalog as $name => $icon) {
            Amenity::firstOrCreate(['name' => $name], ['icon' => $icon]);
        }

        $standardNames = array_keys($standard);
        $superiorNames = [...$standardNames, ...array_keys($superiorAdds)];
        $deluxeNames   = [...$superiorNames, ...array_keys($deluxeAdds)];
        $familyNames   = [...$deluxeNames, ...array_keys($familyAdds)];
        $suiteNames    = [...$deluxeNames, ...array_keys($suiteAdds)];

        $assignments = [
            'Phòng Standard' => $standardNames,
            'Phòng Superior' => $superiorNames,
            'Phòng Deluxe'   => $deluxeNames,
            'Phòng Family'   => $familyNames,
            'Phòng Suite'    => $suiteNames,
        ];

        foreach ($assignments as $roomName => $amenityNames) {
            $roomType = RoomType::where('name', $roomName)->first();

            if (! $roomType) {
                continue;
            }

            $roomType->amenities()->sync(
                Amenity::whereIn('name', $amenityNames)->pluck('id')
            );
        }
    }
}
