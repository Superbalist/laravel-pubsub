<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    |
    | The default pub-sub connection to use.
    |
    | Supported: "/dev/null", "local", "redis", "kafka", "gcloud", "http"
    |
    */

    'default' => env('PUBSUB_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Pub-Sub Connections
    |--------------------------------------------------------------------------
    |
    | The available pub-sub connections to use.
    |
    | A default configuration has been provided for all adapters shipped with
    | the package.
    |
    */

    'connections' => [

        '/dev/null' => [
            'driver' => '/dev/null',
        ],

        'local' => [
            'driver' => 'local',
        ],

        'redis' => [
            'driver' => 'redis',
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
            'read_write_timeout' => 0,
        ],

        'kafka' => [
            'driver' => 'kafka',
            'consumer_group_id' => 'php-pubsub',
            'brokers' => env('KAFKA_BROKERS', 'localhost'),
        ],

        'gcloud' => [
            'driver' => 'gcloud',
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'key_file' => env('GOOGLE_CLOUD_KEY_FILE'),
            'client_identifier' => null,
            'auto_create_topics' => true,
            'auto_create_subscriptions' => true,
            'auth_cache' => null, // eg: \Google\Auth\Cache\MemoryCacheItemPool::class,
            'background_batching' => false,
            'background_daemon' => false,
        ],

        'http' => [
            'driver' => 'http',
            'uri' => env('HTTP_PUBSUB_URI'),
            'subscribe_connection' => env('HTTP_PUBSUB_SUBSCRIBE_CONNECTION', 'redis'),
        ],

    ],

];
