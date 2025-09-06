<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with OpenAI's API. You should set this value to
    | your account's API key which you can retrieve from OpenAI API site.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will timeout after 30 seconds.
    |
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Classification Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the AI classification behavior for tickets.
    |
    */

    'classify_enabled' => env('OPENAI_CLASSIFY_ENABLED', true),
    'rate_limit_per_minute' => env('OPENAI_RATE_LIMIT_PER_MINUTE', 10),

];
