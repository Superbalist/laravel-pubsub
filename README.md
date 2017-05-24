# laravel-pubsub

A Pub-Sub abstraction for Laravel.

[![Author](http://img.shields.io/badge/author-@superbalist-blue.svg?style=flat-square)](https://twitter.com/superbalist)
[![Build Status](https://img.shields.io/travis/Superbalist/laravel-pubsub/master.svg?style=flat-square)](https://travis-ci.org/Superbalist/laravel-pubsub)
[![StyleCI](https://styleci.io/repos/67405993/shield?branch=master)](https://styleci.io/repos/67405993)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/superbalist/laravel-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel-pubsub)
[![Total Downloads](https://img.shields.io/packagist/dt/superbalist/laravel-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel-pubsub)

This package is a wrapper bridging [php-pubsub](https://github.com/Superbalist/php-pubsub) into Laravel.

For **Laravel 4** support, use the package https://github.com/Superbalist/laravel4-pubsub

Please note that **Laravel 5.3** is only supported up until version 2.0.2.

2.0.3+ supports **Laravel 5.4 and up** moving forward.

The following adapters are supported:
* Local
* /dev/null
* Redis
* Kafka (see separate installation instructions below)
* Google Cloud
* HTTP

## Installation

```bash
composer require superbalist/laravel-pubsub
```

Register the service provider in app.php
```php
'providers' => [
    // ...
    Superbalist\LaravelPubSub\PubSubServiceProvider::class,
]
```

Register the facade in app.php
```php
'aliases' => [
    // ...
    'PubSub' => Superbalist\LaravelPubSub\PubSubFacade::class,
]
```

The package has a default configuration which uses the following environment variables.
```
PUBSUB_CONNECTION=redis

REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379

KAFKA_BROKERS=localhost

GOOGLE_CLOUD_PROJECT_ID=your-project-id-here
GOOGLE_CLOUD_KEY_FILE=path/to/your/gcloud-key.json

HTTP_PUBSUB_URI=null
HTTP_PUBSUB_SUBSCRIBE_CONNECTION=redis
```

To customize the configuration file, publish the package configuration using Artisan.
```bash
php artisan vendor:publish --provider="Superbalist\LaravelPubSub\PubSubServiceProvider"
```

You can then edit the generated config at `app/config/pubsub.php`.

## Kafka Adapter Installation

Please note that whilst the package is bundled with support for the [php-pubsub-kafka](https://github.com/Superbalist/php-pubsub-kafka)
adapter, the adapter is not included by default.

This is because the KafkaPubSubAdapter has an external dependency on the `librdkafka c library` and the `php-rdkafka`
PECL extension.

If you plan on using this adapter, you will need to install these dependencies by following these [installation instructions](https://github.com/Superbalist/php-pubsub-kafka).

You can then include the adapter using:
```bash
composer require superbalist/php-pubsub-kafka
```

## Usage

```php
// get the pub-sub manager
$pubsub = app('pubsub');

// note: function calls on the manager are proxied through to the default connection
// eg: you can do this on the manager OR a connection
$pubsub->publish('channel_name', 'message');

// get the default connection
$pubsub = app('pubsub.connection');
// or
$pubsub = app(\Superbalist\PubSub\PubSubAdapterInterface::class);

// get a specific connection
$pubsub = app('pubsub')->connection('redis');

// publish a message
// the message can be a string, array, bool, object - anything which can be json encoded
$pubsub->publish('channel_name', 'this is where your message goes');
$pubsub->publish('channel_name', ['key' => 'value']);
$pubsub->publish('channel_name', true);

// publish multiple messages
$messages = [
    'message 1',
    'message 2',
];
$pubsub->publishBatch('channel_name', $messages);

// subscribe to a channel
$pubsub->subscribe('channel_name', function ($message) {
    var_dump($message);
});

// all the above commands can also be done using the facade
PubSub::connection('kafka')->publish('channel_name', 'Hello World!');

PubSub::connection('kafka')->subscribe('channel_name', function ($message) {
    var_dump($message);
});
```

## Creating a Subscriber

The package includes a helper command `php artisan make:subscriber MyExampleSubscriber` to stub new subscriber command classes.

A lot of pub-sub adapters will contain blocking `subscribe()` calls, so these commands are best run as daemons running
as a [supervisor](http://supervisord.org) process.

This generator command will create the file `app/Console/Commands/MyExampleSubscriber.php` which will contain:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Superbalist\PubSub\PubSubAdapterInterface;

class MyExampleSubscriber extends Command
{
    /**
     * The name and signature of the subscriber command.
     *
     * @var string
     */
    protected $signature = 'subscriber:name';

    /**
     * The subscriber description.
     *
     * @var string
     */
    protected $description = 'PubSub subscriber for ________';

    /**
     * @var PubSubAdapterInterface
     */
    protected $pubsub;

    /**
     * Create a new command instance.
     *
     * @param PubSubAdapterInterface $pubsub
     */
    public function __construct(PubSubAdapterInterface $pubsub)
    {
        parent::__construct();

        $this->pubsub = $pubsub;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->pubsub->subscribe('channel_name', function ($message) {

        });
    }
}
```

### Kafka Subscribers ###

For subscribers which use the `php-pubsub-kafka` adapter, you'll likely want to change the `consumer_group_id` per
subscriber.

To do this, you need to use the `PubSubConnectionFactory` to create a new connection per subscriber.  This is because
the `consumer_group_id` cannot be changed once a connection is created.

Here is an example of how you can do this:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Superbalist\LaravelPubSub\PubSubConnectionFactory;
use Superbalist\PubSub\PubSubAdapterInterface;

class MyExampleKafkaSubscriber extends Command
{
    /**
     * The name and signature of the subscriber command.
     *
     * @var string
     */
    protected $signature = 'subscriber:name';

    /**
     * The subscriber description.
     *
     * @var string
     */
    protected $description = 'PubSub subscriber for ________';

    /**
     * @var PubSubAdapterInterface
     */
    protected $pubsub;

    /**
     * Create a new command instance.
     *
     * @param PubSubConnectionFactory $factory
     */
    public function __construct(PubSubConnectionFactory $factory)
    {
        parent::__construct();

        $config = config('pubsub.connections.kafka');
        $config['consumer_group_id'] = self::class;
        $this->pubsub = $factory->make('kafka', $config);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->pubsub->subscribe('channel_name', function ($message) {

        });
    }
}
```

## Adding a Custom Driver

Please see the [php-pubsub](https://github.com/Superbalist/php-pubsub) documentation  **Writing an Adapter**.

To include your custom driver, you can call the `extend()` function.

```php
$manager = app('pubsub');
$manager->extend('custom_connection_name', function ($config) {
    // your callable must return an instance of the PubSubAdapterInterface
    return new MyCustomPubSubDriver($config);
});

// get an instance of your custom connection
$pubsub = $manager->connection('custom_connection_name');
```
