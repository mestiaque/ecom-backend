<?php

return [
    'name' => 'ecom',

    /*
    | Currency shown in the admin panel. The PDF invoice uses currency_code because
    | the PDF font has no glyph for "৳".
    */
    'currency_symbol' => '৳',
    'currency_code' => 'BDT',

    // Low-stock warning level for products without their own threshold
    'low_stock_threshold' => 5,

    // Folder on the "public" disk for uploaded images (needs: php artisan storage:link)
    'upload_dir' => 'ecom',

    /*
    | Courier API base URLs. The sandbox URL is used when the courier's mode is "sandbox"
    | (Couriers settings page). {tracking} in "tracking" is replaced by the tracking code.
    */
    'couriers' => [
        'steadfast' => [
            'label' => 'Steadfast',
            'live' => 'https://portal.packzy.com/api/v1',
            'sandbox' => 'https://portal.packzy.com/api/v1',
            'tracking' => 'https://steadfast.com.bd/t/{tracking}',
        ],
        'pathao' => [
            'label' => 'Pathao',
            'live' => 'https://api-hermes.pathao.com',
            'sandbox' => 'https://courier-api-sandbox.pathao.com',
            'tracking' => 'https://merchant.pathao.com/tracking?consignment_id={tracking}',
        ],
        'redx' => [
            'label' => 'RedX',
            'live' => 'https://openapi.redx.com.bd/v1.0.0-beta',
            'sandbox' => 'https://sandbox.redx.com.bd/v1.0.0-beta',
            'tracking' => 'https://redx.com.bd/track-parcel/?trackingId={tracking}',
        ],
    ],
];
