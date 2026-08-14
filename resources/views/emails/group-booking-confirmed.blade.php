@php
    $hotel = \App\Models\HotelInfo::instance();
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt phòng đoàn/nhóm</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; max-width:600px; width:100%;">
                    <tr>
                        <td style="background:#2563eb; padding:24px 32px;">
                            <div style="font-size:20px; font-weight:bold; color:#ffffff;">{{ $hotel->name }}</div>
                            <div style="font-size:13px; color:#dbeafe; margin-top:2px;">Xác nhận đặt phòng đoàn/nhóm</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                                Chào {{ $booking->customer_name }},<br>
                                {{ $hotel->name }} đã tạo đơn đặt phòng đoàn/nhóm cho anh/chị. Thông tin chi tiết như sau:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:16px;">
                                <tr>
                                    <td style="padding:6px 0; color:#64748b;">Mã đơn</td>
                                    <td style="padding:6px 0; text-align:right; font-weight:bold;">{{ $booking->booking_code }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b;">Trạng thái</td>
                                    <td style="padding:6px 0; text-align:right; font-weight:bold;">{{ $booking->status->label() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b;">Nhận phòng</td>
                                    <td style="padding:6px 0; text-align:right;">{{ $booking->check_in->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b;">Trả phòng</td>
                                    <td style="padding:6px 0; text-align:right;">{{ $booking->check_out->format('d/m/Y') }} ({{ $booking->nights }} đêm)</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:16px;">
                                <thead>
                                    <tr style="background:#f1f5f9;">
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0;">Loại phòng</td>
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:center;">SL</td>
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:right;">Giá/đêm</td>
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:right;">Thành tiền</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->bookingItems as $item)
                                        <tr>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9;">{{ $item->roomType->name }}</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:center;">{{ $item->quantity }}</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                @if ($booking->discount_amount > 0)
                                    <tr>
                                        <td style="padding:2px 0; font-size:13px; color:#64748b;">Giảm giá</td>
                                        <td style="padding:2px 0; font-size:13px; text-align:right; color:#dc2626;">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="background:#dbeafe; border-radius:8px; padding:12px 16px;" colspan="2">
                                        <span style="font-size:14px; font-weight:bold; color:#1e293b;">Tổng cộng</span>
                                        <span style="float:right; font-size:16px; font-weight:bold; color:#2563eb;">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
                                    </td>
                                </tr>
                            </table>

                            @if ($booking->note)
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; white-space:pre-line;"><strong>Ghi chú:</strong> {{ $booking->note }}</p>
                            @endif

                            <p style="margin:0; font-size:13px; color:#64748b; line-height:1.6;">
                                Mọi thắc mắc, anh/chị vui lòng liên hệ lại {{ $hotel->name }}
                                @if ($hotel->phone) qua số điện thoại <strong>{{ $hotel->phone }}</strong> @endif
                                @if ($hotel->email) hoặc email <strong>{{ $hotel->email }}</strong> @endif
                                để được hỗ trợ thêm.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px; background:#f8fafc; text-align:center; font-size:12px; color:#94a3b8;">
                            {{ $hotel->name }}@if ($hotel->address) · {{ $hotel->address }} @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
