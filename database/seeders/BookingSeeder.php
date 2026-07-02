<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
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

        $secondCustomer = User::where('email', 'user@gmail.com')->first() ?? $customer;

        $standardRoom = RoomType::where('name', 'Phòng Standard')->first();
        $superiorRoom = RoomType::where('name', 'Phòng Superior')->first();
        $suiteRoom    = RoomType::where('name', 'Phòng Suite')->first();

        // ---------- Đơn 1: pending, khách vừa đặt ----------
        if ($standardRoom && ! Booking::where('booking_code', 'HOMI-DEMO-PENDING')->exists()) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-PENDING',
                'user_id'        => $customer->id,
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
        if ($superiorRoom && ! Booking::where('booking_code', 'HOMI-DEMO-CONFIRMED')->exists()) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-CONFIRMED',
                'user_id'        => $customer->id,
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
        if ($standardRoom && ! Booking::where('booking_code', 'HOMI-DEMO-CANCELLED')->exists()) {
            $b = Booking::create([
                'booking_code'   => 'HOMI-DEMO-CANCELLED',
                'user_id'        => $customer->id,
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

        // ---------- Đơn 4-6: Tuần 9 (Sprint 5) — 3 đơn giao nhau trên Phòng
        // Suite (total_rooms = 3) để demo availability hết phòng khi có nhiều
        // booking giao nhau trong khoảng 21-22/07. Ngoài khoảng này vẫn còn
        // phòng trống để demo "còn phòng". ----------
        if ($suiteRoom) {
            $overlapBookings = [
                ['code' => 'HOMI-DEMO-OVERLAP-1', 'in' => '2026-07-18', 'out' => '2026-07-22', 'who' => $customer],
                ['code' => 'HOMI-DEMO-OVERLAP-2', 'in' => '2026-07-20', 'out' => '2026-07-25', 'who' => $secondCustomer],
                ['code' => 'HOMI-DEMO-OVERLAP-3', 'in' => '2026-07-21', 'out' => '2026-07-23', 'who' => $customer],
            ];

            foreach ($overlapBookings as $data) {
                if (Booking::where('booking_code', $data['code'])->exists()) {
                    continue;
                }

                $nights = (new \DateTime($data['in']))->diff(new \DateTime($data['out']))->days;
                $subtotal = $suiteRoom->price_per_night * $nights;

                $b = Booking::create([
                    'booking_code'   => $data['code'],
                    'user_id'        => $data['who']->id,
                    'check_in'       => $data['in'],
                    'check_out'      => $data['out'],
                    'nights'         => $nights,
                    'customer_name'  => $data['who']->name,
                    'customer_email' => $data['who']->email,
                    'customer_phone' => $data['who']->phone ?? '0900000001',
                    'total_amount'   => $subtotal,
                    'status'         => 'confirmed',
                ]);
                BookingItem::create([
                    'booking_id'      => $b->id,
                    'room_type_id'    => $suiteRoom->id,
                    'quantity'        => 1,
                    'price_per_night' => $suiteRoom->price_per_night,
                    'nights'          => $nights,
                    'subtotal'        => $subtotal,
                ]);
                Payment::create([
                    'booking_id' => $b->id,
                    'method'     => 'pay_at_hotel',
                    'amount'     => $subtotal,
                    'status'     => 'unpaid',
                ]);
            }
        }
    }
}
