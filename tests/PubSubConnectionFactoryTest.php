<?php

namespace Tests;

use Google\Cloud\PubSub\PubSubClient as GoogleCloudPubSubClient;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Mockery;
use Predis\Client as RedisClient;
use Superbalist\LaravelPubSub\PubSubConnectionFactory;
use Superbalist\PubSub\Adapters\DevNullPubSubAdapter;
use Superbalist\PubSub\Adapters\LocalPubSubAdapter;
use Superbalist\PubSub\GoogleCloud\GoogleCloudPubSubAdapter;
use Superbalist\PubSub\Kafka\KafkaPubSubAdapter;
use Superbalist\PubSub\Redis\RedisPubSubAdapter;

class PubSubConnectionFactoryTest extends TestCase
{
    public function testMakeDevNullAdapter()
    {
        $container = Mockery::mock(Container::class);

        $factory = new PubSubConnectionFactory($container);

        $adapter = $factory->make('/dev/null');
        $this->assertInstanceOf(DevNullPubSubAdapter::class, $adapter);
    }

    public function testMakeLocalAdapter()
    {
        $container = Mockery::mock(Container::class);

        $factory = new PubSubConnectionFactory($container);

        $adapter = $factory->make('local');
        $this->assertInstanceOf(LocalPubSubAdapter::class, $adapter);
    }

    public function testMakeRedisAdapter()
    {
        $config = [
            'scheme' => 'tcp',
            'host' => 'localhost',
            'password' => null,
            'port' => 6379,
            'database' => 0,
            'read_write_timeout' => 0,
        ];

        $container = Mockery::mock(Container::class);
        $container->shouldReceive('make')
            ->withArgs([
                'pubsub.redis.redis_client',
                [$config]
            ])
            ->once()
            ->andReturn(Mockery::mock(RedisClient::class));

        $factory = new PubSubConnectionFactory($container);

        $adapter = $factory->make('redis', $config);
        $this->assertInstanceOf(RedisPubSubAdapter::class, $adapter);
    }

    public function testMakeKafkaAdapter()
    {
        if (!class_exists('\Superbalist\PubSub\Kafka\KafkaPubSubAdapter')) {
            $this->markTestSkipped('KafkaPubSubAdapter is not installed');
        }

        $container = Mockery::mock(Container::class);

        $topicConf = Mockery::mock(\RdKafka\TopicConf::class);
        $topicConf->shouldReceive('set');

        $container->shouldReceive('make')
            ->with('pubsub.kafka.topic_conf')
            ->once()
            ->andReturn($topicConf);

        $producer = Mockery::mock(\RdKafka\Producer::class);
        $producer->shouldReceive('addBrokers')
            ->with('localhost')
            ->once()
            ->once();

        $container->shouldReceive('make')
            ->with('pubsub.kafka.producer')
            ->once()
            ->andReturn($producer);

        $consumer = Mockery::mock(\RdKafka\Consumer::class);
        $consumer->shouldReceive('addBrokers')
            ->with('localhost')
            ->once();

        $container->shouldReceive('make')
            ->with('pubsub.kafka.consumer')
            ->once()
            ->andReturn($consumer);

        $factory = new PubSubConnectionFactory($container);

        $adapter = $factory->make('kafka', ['brokers' => 'localhost']);
        $this->assertInstanceOf(KafkaPubSubAdapter::class, $adapter);
    }

    public function testMakeGoogleCloudAdapter()
    {
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('make')
            ->withArgs([
                'pubsub.gcloud.pub_sub_client',
                [
                    [
                        'projectId' => 12345,
                        'keyFilePath' => 'my_key_file.json',
                    ]
                ]
            ])
            ->andReturn(Mockery::mock(GoogleCloudPubSubClient::class));

        $factory = new PubSubConnectionFactory($container);

        $config = [
            'project_id' => '12345',
            'key_file' => 'my_key_file.json',
        ];
        $adapter = $factory->make('gcloud', $config);
        $this->assertInstanceOf(GoogleCloudPubSubAdapter::class, $adapter);
    }

    public function testMakeInvalidAdapterThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver [rubbish] is not supported.');

        $container = Mockery::mock(Container::class);

        $factory = new PubSubConnectionFactory($container);

        $factory->make('rubbish');
    }
}
