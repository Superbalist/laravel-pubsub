# laravel-pubsub

A Pub-Sub abstraction for Laravel.

[![Author](http://img.shields.io/badge/author-@superbalist-blue.svg?style=flat-square)](https://twitter.com/superbalist)
[![Build Status](https://img.shields.io/travis/Superbalist/laravel-pubsub/master.svg?style=flat-square)](https://travis-ci.org/Superbalist/laravel-pubsub)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/superbalist/laravel-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel-pubsub)
[![Total Downloads](https://img.shields.io/packagist/dt/superbalist/laravel-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel-pubsub)

This package is a wrapper bridging [php-pubsub](https://github.com/Superbalist/php-pubsub) into Laravel.

The following adapters are supported:
* Local
* /dev/null
* Redis
* Kafka
* Google Cloud

## Installation

```bash
composer require superbalist/laravel-pubsub
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
```

To customize the configuration file, publish the package configuration using Artisan.
```bash
php artisan vendor:publish --provider="Superbalist\LaravelPubSub\PubSubServiceProvider"
```

You can then edit the generated config at `app/config/pubsub.php`.

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

## Usage

```php
// TODO:
```

## TODO

* Documentation
* Unit Tests