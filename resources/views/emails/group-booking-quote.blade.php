@php
    $hotel = \App\Models\HotelInfo::instance();
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo giá đặt đoàn/nhóm</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; max-width:600px; width:100%;">
                    <tr>
                        <td style="background:#2563eb; padding:24px 32px;">
                            <div style="font-size:20px; font-weight:bold; color:#ffffff;">{{ $hotel->name }}</div>
                            <div style="font-size:13px; color:#dbeafe; margin-top:2px;">Báo giá đặt phòng đoàn/nhóm</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                                Chào {{ $groupRequest->contact_name }},<br>
                                Cảm ơn anh/chị đã gửi yêu cầu đặt phòng đoàn/nhóm #{{ $groupRequest->id }} tới {{ $hotel->name }}. Dưới đây là báo giá sơ bộ:
                            </p>

                            @if ($quote['nights'])
                                <p style="margin:0 0 16px; font-size:14px;">
                                    <strong>Thời gian lưu trú:</strong>
                                    {{ $groupRequest->check_in->format('d/m/Y') }} → {{ $groupRequest->check_out->format('d/m/Y') }}
                                    ({{ $quote['nights'] }} đêm)
                                </p>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:16px;">
                                <thead>
                                    <tr style="background:#f1f5f9;">
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0;">Loại phòng</td>
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:center;">SL</td>
                                        <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:right;">Giá/đêm</td>
                                        @if ($quote['nights'])
                                            <td style="padding:8px 10px; font-weight:bold; border-bottom:1px solid #e2e8f0; text-align:right;">Thành tiền</td>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quote['items'] as $item)
                                        <tr>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9;">{{ $item['name'] }}</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:center;">{{ $item['quantity'] }}</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($item['price_per_night'], 0, ',', '.') }}đ</td>
                                            @if ($quote['nights'])
                                                <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($item['subtotal'], 0, ',', '.') }}đ</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    @if ($quote['extra_beds'] > 0)
                                        <tr>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9;">Giường phụ trẻ em (6-11 tuổi)</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:center;">{{ $quote['extra_beds'] }}</td>
                                            <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($quote['extra_bed_price_per_night'], 0, ',', '.') }}đ</td>
                                            @if ($quote['nights'])
                                                <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:right;">{{ number_format($quote['extra_bed_subtotal'], 0, ',', '.') }}đ</td>
                                            @endif
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            @if ($quote['nights'])
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                    <tr>
                                        <td style="background:#dbeafe; border-radius:8px; padding:12px 16px;">
                                            <span style="font-size:14px; font-weight:bold; color:#1e293b;">Tổng dự kiến</span>
                                            <span style="float:right; font-size:16px; font-weight:bold; color:#2563eb;">{{ number_format($quote['total'], 0, ',', '.') }}đ</span>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 16px; font-size:12px; color:#64748b;">(Chưa bao gồm dịch vụ phát sinh trong lúc lưu trú, nếu có.)</p>
                            @endif

                            @if ($quote['note'])
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; white-space:pre-line;">{{ $quote['note'] }}</p>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 20px;">
                                <tr>
                                    <td style="border-radius:8px; background:#2563eb;">
                                        <a href="{{ route('rooms.index') }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">Xem phòng và đặt ngay</a>
                                    </td>
                                </tr>
                            </table>

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
