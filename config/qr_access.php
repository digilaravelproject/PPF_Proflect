<?php

return [
    /*
    | Keep this enabled in production. Tests disable it in phpunit.xml so the
    | existing feature suite can exercise application routes directly.
    */
    'enabled' => (bool) env('QR_ACCESS_ENABLED', true),

    /*
    | An optional fixed token. When omitted, a stable token is derived from
    | APP_KEY, so the QR remains unchanged unless APP_KEY or APP_URL changes.
    */
    'token' => env('QR_ACCESS_TOKEN'),

    'session_key' => 'proflect.qr_access_granted',
];
