<?php

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    protected function getMockPubSubConfig()
    {
        return [
            'default' => '/dev/null',
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
                    'host' => 'localhost',
                    'password' => null,
                    'port' => 6379,
                    'database' => 0,
                    'read_write_timeout' => 0,
                ],
                'kafka' => [
                    'driver' => 'kafka',
                    'consumer_group_id' => 'php-pubsub',
                    'brokers' => 'localhost',
                ],
                'http' => [
                    'driver' => 'http',
                    'uri' => 'http://127.0.0.1',
                    'subscribe_connection' => '/dev/null',
                ],
                'missing_driver' => [

                ],
                'custom_connection' => [

                ],
            ],
        ];
    }
}
