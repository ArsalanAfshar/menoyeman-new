<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    | "log"     — fake driver for local development/testing. Messages are
    |             written to the Laravel log and (when "fake_display" is on)
    |             the OTP is shown on screen so you can log in without a phone.
    | "smsir"   — sms.ir fast-send/verify template API (configured in .env,
    |             implemented in App\Services\Sms\SmsIrDriver).
    */

    'driver' => env('SMS_DRIVER', 'log'),

    'fake_display' => env('SMS_FAKE_DISPLAY', env('APP_ENV') !== 'production'),

    'from' => env('SMS_FROM'),

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => env('SMSIR_TEMPLATE_ID'),
        'base_url' => env('SMSIR_BASE_URL', 'https://api.sms.ir/v1/'),
    ],

];
