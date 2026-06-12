<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@homi.test')->first();
        if (! $customer) {
            return;
        }

        $danangHotel  = Hotel::where('name', 'Homi Đà Nẵng Hotel')->first();
        $hanoiHotel   = Hotel::where('name', 'Homi Hà Nội Hotel')->first();

        $standardRoom = RoomType::where('hotel_id', $danangHotel?->id)
            ->where('name', 'Phòng Standard')
            ->first();

        $superiorRoom = RoomType::where('hotel_id', $hanoiHotel?->id)
            ->where('name', 'Phòng Superior')
            ->first();

        // ---------- Đơn 1: pending, khách vừa đặt ----------
        if ($danangHotel && $standardRoom) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-PENDING',
                'user_id'        => $customer->id,
                'hotel_id'       => $danangHotel->id,
                'check_in'       => '2026-07-10',
                'check_out'      => '2026-07-12',
                'nights'         => 2,
                'customer_name'  => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? '0900000001',
                'total_amount'   => $standardRoom->price_per_night * 2,
                'status'         => 'pending',
            ]);
            BookingItem::create([
                'booking_id'      => $b->id,
                'room_type_id'    => $standardRoom->id,
                'quantity'        => 1,
                'price_per_night' => $standardRoom->price_per_night,
                'nights'          => 2,
                'subtotal'        => $standardRoom->price_per_night * 2,
            ]);
            Payment::create([
                'booking_id' => $b->id,
                'method'     => 'pay_at_hotel',
                'amount'     => $standardRoom->price_per_night * 2,
                'status'     => 'unpaid',
            ]);
        }

        // ---------- Đơn 2: confirmed + paid ----------
        if ($hanoiHotel && $superiorRoom) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-CONFIRMED',
                'user_id'        => $customer->id,
                'hotel_id'       => $hanoiHotel->id,
                'check_in'       => '2026-08-01',
                'check_out'      => '2026-08-04',
                'nights'         => 3,
                'customer_name'  => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? '0900000001',
                'total_amount'   => $superiorRoom->price_per_night * 3,
                'status'         => 'confirmed',
            ]);
            BookingItem::create([
                'booking_id'      => $b->id,
                'room_type_id'    => $superiorRoom->id,
                'quantity'        => 1,
                'price_per_night' => $superiorRoom->price_per_night,
                'nights'          => 3,
                'subtotal'        => $superiorRoom->price_per_night * 3,
            ]);
            Payment::create([
                'booking_id' => $b->id,
                'method'     => 'bank_transfer',
                'amount'     => $superiorRoom->price_per_night * 3,
                'status'     => 'paid',
                'paid_at'    => now(),
            ]);
        }

        // ---------- Đơn 3: cancelled bởi khách ----------
        if ($danangHotel && $standardRoom) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-CANCELLED',
                'user_id'        => $customer->id,
                'hotel_id'       => $danangHotel->id,
                'check_in'       => '2026-09-05',
                'check_out'      => '2026-09-07',
                'nights'         => 2,
                'customer_name'  => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? '0900000001',
                'total_amount'   => $standardRoom->price_per_night * 2,
                'status'         => 'cancelled',
                'note'           => 'Khách hủy do thay đổi kế hoạch.',
            ]);
            BookingItem::create([
                'booking_id'      => $b->id,
                'room_type_id'    => $standardRoom->id,
                'quantity'        => 1,
                'price_per_night' => $standardRoom->price_per_night,
                'nights'          => 2,
                'subtotal'        => $standardRoom->price_per_night * 2,
            ]);
            Payment::create([
                'booking_id' => $b->id,
                'method'     => 'pay_at_hotel',
                'amount'     => $standardRoom->price_per_night * 2,
                'status'     => 'refunded',
            ]);
        }
    }
}
