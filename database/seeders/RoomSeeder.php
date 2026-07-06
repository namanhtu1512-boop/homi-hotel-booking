<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * Sinh phòng vật lý (số phòng thật) cho từng loại phòng đã seed ở
 * RoomTypeSeeder, đúng bằng total_rooms — để tính năng check-in có phòng
 * thật để gán ngay sau khi cài đặt. Mỗi loại phòng chiếm 1 tầng riêng
 * (tầng = thứ tự loại phòng), số phòng dạng {tầng}{01..N}.
 */
class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = RoomType::orderBy('id')->get();

        foreach ($roomTypes as $index => $roomType) {
            $floor = $index + 1;

            for ($i = 1; $i <= $roomType->total_rooms; $i++) {
                $roomNumber = $floor . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

                Room::firstOrCreate(['room_number' => $roomNumber], [
                    'room_type_id'        => $roomType->id,
                    'housekeeping_status' => 'clean',
                ]);
            }
        }
    }
}
