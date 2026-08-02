<?php

namespace App\Http\Controllers\Api\Concerns;

trait FormatsBookingForApi
{
    protected function formatBooking($booking): array
    {
        return [
            'id'              => $booking->id,
            'booking_code'    => $booking->booking_code,
            'check_in'        => $booking->check_in->toDateString(),
            'check_out'       => $booking->check_out->toDateString(),
            'nights'          => $booking->nights,
            'customer_name'   => $booking->customer_name,
            'customer_phone'  => $booking->customer_phone,
            'customer_email'  => $booking->customer_email,
            'total_amount'    => $booking->total_amount,
            'discount_amount' => $booking->discount_amount,
            'status'          => $booking->status->value,
            'status_label'    => $booking->status->label(),
            'note'            => $booking->note,
            'items'           => $booking->bookingItems->map(fn ($item) => [
                'room_type_id'    => $item->room_type_id,
                'room_type_name'  => $item->roomType?->name,
                'quantity'        => $item->quantity,
                'adults'          => $item->adults,
                'children'        => $item->children,
                'infants'         => $item->infants,
                'price_per_night' => $item->price_per_night,
                'nights'          => $item->nights,
                'subtotal'        => $item->subtotal,
                'child_surcharge' => $item->child_surcharge,
                'price_breakdown' => $item->price_breakdown,
            ])->all(),
            'promotions' => $booking->promotions->map(fn ($promo) => [
                'code'            => $promo->code,
                'discount_amount' => (int) $promo->pivot->discount_amount,
            ])->all(),
            'services' => $booking->serviceItems->map(fn ($item) => [
                'service_id'   => $item->service_id,
                'service_name' => $item->service?->name,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'subtotal'     => $item->subtotal,
            ])->all(),
            'payment' => $booking->payment ? [
                'status'        => $booking->payment->status->value,
                'status_label'  => $booking->payment->status->label(),
                'method'        => $booking->payment->method->value,
                'method_label'  => $booking->payment->method->label(),
                'amount'        => $booking->payment->amount,
            ] : null,
            'created_at' => $booking->created_at->toIso8601String(),
        ];
    }
}
