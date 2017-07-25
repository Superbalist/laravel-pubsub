# Changelog

## 3.0.0 - 2017-07-25

* Bump up to superbalist/php-pubsub-google-cloud ^5.0 which allows for background daemon support
* Add new background_batching and background_daemon options to Google Cloud adapter

## 2.0.5 - 2017-07-03

* Add support for using a custom auth cache with the Google Cloud adapter

## 2.0.4 - 2017-05-24

* Add support for HTTP adapter

## 2.0.3 - 2017-05-19

* Bump illuminate/support & illuminate/console to ^5.4
* Fix compatibility with Laravel 5.4 by switching to makeWith method on container (@mathieutu)

## 2.0.2 - 2017-05-16

* Allow for superbalist/php-pubsub ^2.0
* Allow for superbalist/php-pubsub-redis ^2.0
* Allow for superbalist/php-pubsub-google-cloud ^4.0

## 2.0.1 - 2017-01-03

* Allow for superbalist/php-pubsub-google-cloud ^3.0

## 2.0.0 - 2016-10-05

* Bump up version ^2.0 of superbalist/php-pubsub-google-cloud
* Add support for `client_identifier` config var added to superbalist/php-pubsub-google-cloud package

## 1.0.2 - 2016-09-15

* Add support for configuring auto topic & subscription creation for the php-pubsub-google-cloud adapter

## 1.0.1 - 2016-09-08

* Fix for breaking change in php-pubsub-kafka package
* Fix params passed to Laravel's container->make() function

## 1.0.0 - 2016-09-06

* Initial release