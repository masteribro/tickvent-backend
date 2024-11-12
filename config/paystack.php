<?php

return [
    'secret_key' => env('APP_ENV') === 'local' ? env('PAYSTACK_TEST_KEY') : env('PAYSTACK_LIVE_KEY'),
    'base_url' => "https://api.paystack.co",
];

