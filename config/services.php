<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        // Bản "lite" — không có bước "suy nghĩ" (thinking) trước khi trả lời
        // như bản flash thường, nhanh hơn ~3-4 lần cho mỗi lượt gọi API mà
        // vẫn đủ chính xác cho phạm vi hẹp (tra cứu phòng/giá qua tool) của
        // trợ lý này.
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
    ],

    'vnpay' => [
        'tmn_code'    => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'pay_url'     => env('VNPAY_PAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'api_url'     => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),

        // Thời gian TỐI ĐA (phút) 1 phiên thanh toán VNPay còn hiệu lực kể từ
        // lúc khách bấm "Thanh toán VNPay" — nguồn DUY NHẤT cho giá trị này,
        // không hardcode lặp lại ở nơi khác. BookingService::initiateVnpayPayment()
        // luôn cấp cho VNPay mốc SỚM HƠN giữa "bây giờ + giá trị này" và "hạn
        // giữ chỗ hiện tại của booking" — nên phiên VNPay không bao giờ vượt
        // quá hold, và khách thấy đồng hồ VNPay là phần CÒN LẠI của đồng hồ
        // giữ chỗ (liên tục, không reset). An toàn cho IPN tới trễ sau khi
        // hold hết hạn nằm ở services.booking.expired_grace_minutes bên dưới.
        'txn_expire_minutes' => (int) env('VNPAY_TXN_EXPIRE_MINUTES', 15),
    ],

    'booking' => [
        // Sau khi hold giữ phòng (deposit_expires_at) hết hạn, booking KHÔNG
        // bị hủy/nhả phòng ngay — chuyển sang BookingStatus::EXPIRED_PENDING_CHECK
        // và giữ thêm khoảng đệm này để chờ IPN/return VNPay tới trễ, trước
        // khi thực sự hủy hẳn (xem BookingService::processBookingExpiry()).
        'expired_grace_minutes' => (int) env('BOOKING_EXPIRED_GRACE_MINUTES', 5),
    ],

    // Tài khoản nhận chuyển khoản ngân hàng — hiển thị QR VietQR + số tài
    // khoản ở trang thanh toán khách hàng (thay cho cổng VNPay sandbox đang
    // lỗi). BIN 970422 = MB Bank (danh sách BIN NAPAS chuẩn, không đổi theo
    // tài khoản cụ thể).
    'bank_transfer' => [
        'bin'          => env('BANK_TRANSFER_BIN', '970422'),
        'account_no'   => env('BANK_TRANSFER_ACCOUNT_NO'),
        'account_name' => env('BANK_TRANSFER_ACCOUNT_NAME'),
        'bank_name'    => env('BANK_TRANSFER_BANK_NAME', 'MB Bank (Ngân hàng TMCP Quân đội)'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

];
