<?php

return [
    'test_mode' => env('FAWATERAK_TEST_MODE', true), // TEST MODE by default
    'api_key' => env('FAWATERAK_API_KEY'),
    'base_url' => env('FAWATERAK_BASE_URL', 'https://app.fawaterk.com/api/v2'),
    'webhook_url' => env('FAWATERAK_WEBHOOK_URL'),
    'callback_url' => env('FAWATERAK_CALLBACK_URL'),
    'success_url' => env('FAWATERAK_SUCCESS_URL'),
    'failure_url' => env('FAWATERAK_FAILURE_URL'),
];
