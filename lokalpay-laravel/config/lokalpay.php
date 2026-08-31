<?php

return [
    'currency' => 'PLN',
    'super_admin_email' => strtolower((string) env('LOKALPAY_SUPER_ADMIN_EMAIL', '')),
    'default_payment_provider' => env('LOKALPAY_PAYMENT_PROVIDER', 'stripe'),
    'demo_seed' => (bool) env('DEMO_SEED', false),
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
    'plans' => [
        'free' => [
            'name' => 'Free', 'price_cents' => 0, 'property_limit' => 3,
            'features' => ['portfolio', 'leases', 'obligations', 'offline_payments'],
        ],
        'growth' => [
            'name' => 'Growth', 'price_cents' => 4900, 'property_limit' => 10,
            'features' => ['portfolio', 'leases', 'obligations', 'offline_payments', 'forecasts', 'year_comparison'],
        ],
        'pro' => [
            'name' => 'Pro', 'price_cents' => 12900, 'property_limit' => 50,
            'features' => ['portfolio', 'leases', 'obligations', 'offline_payments', 'forecasts', 'year_comparison', 'advanced_analytics', 'priority_support'],
        ],
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'prices' => ['growth' => env('STRIPE_PRICE_GROWTH'), 'pro' => env('STRIPE_PRICE_PRO')],
    ],
    'payu' => [
        'environment' => env('PAYU_ENVIRONMENT', 'sandbox'),
        'pos_id' => env('PAYU_POS_ID'),
        'client_id' => env('PAYU_CLIENT_ID'),
        'client_secret' => env('PAYU_CLIENT_SECRET'),
        'second_key' => env('PAYU_SECOND_KEY'),
    ],
];
