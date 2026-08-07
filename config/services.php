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

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
    ],

    'vnpay' => [
        'tmn_code'    => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'pay_url'     => env('VNPAY_PAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'api_url'     => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
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
