<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Mockery;
use Superbalist\LaravelPubSub\PubSubConnectionFactory;
use Superbalist\LaravelPubSub\PubSubManager;
use Superbalist\PubSub\Adapters\DevNullPubSubAdapter;
use Superbalist\PubSub\PubSubAdapterInterface;

class PubSubManagerTest extends TestCase
{
    public function testConnectionWithNullNameReturnsDefaultConnection()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);
        $factory->shouldReceive('make')
            ->withArgs([
                '/dev/null',
                [],
            ])
            ->once()
            ->andReturn(PubSubAdapterInterface::class);

        $manager = new PubSubManager($app, $factory);

        $this->assertEmpty($manager->getConnections());
        $manager->connection();
        $connections = $manager->getConnections();
        $this->assertEquals(1, count($connections));
        $this->assertArrayHasKey('/dev/null', $connections);
    }

    public function testConnectionWithInvalidConnectionNameThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The pub-sub connection [invalid_name] is not configured.');

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);

        $manager = new PubSubManager($app, $factory);

        $manager->connection('invalid_name');
    }

    public function testConnectionWithMissingDriverConfigThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The pub-sub connection [missing_driver] is missing a "driver" config var.');

        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);

        $manager = new PubSubManager($app, $factory);

        $manager->connection('missing_driver');
    }

    public function testConnectionWithNameReturnsSpecifiedConnection()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);
        $factory->shouldReceive('make')
            ->withArgs([
                'kafka',
                [
                    'consumer_group_id' => 'php-pubsub',
                    'brokers' => 'localhost',
                ],
            ])
            ->once()
            ->andReturn(PubSubAdapterInterface::class);

        $manager = new PubSubManager($app, $factory);

        $this->assertEmpty($manager->getConnections());
        $manager->connection('kafka');
        $connections = $manager->getConnections();
        $this->assertEquals(1, count($connections));
        $this->assertArrayHasKey('kafka', $connections);
    }

    public function testConnectionWithExistingConnectionReturnsThatConnection()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $adapter = Mockery::mock(DevNullPubSubAdapter::class);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);
        $factory->shouldReceive('make')
            ->withArgs([
                '/dev/null',
                [],
            ])
            ->once()
            ->andReturn($adapter);

        $manager = new PubSubManager($app, $factory);

        $this->assertEmpty($manager->getConnections());

        $connection1 = $manager->connection('/dev/null');
        $this->assertSame($adapter, $connection1);
        $connections = $manager->getConnections();
        $this->assertEquals(1, count($connections));
        $this->assertArrayHasKey('/dev/null', $connections);

        $connection2 = $manager->connection('/dev/null');
        $this->assertSame($connection1, $connection2);
        $this->assertEquals(1, count($manager->getConnections()));
    }

    public function testConnectionWithCustomExtension()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);
        $factory->shouldNotReceive('make');

        $manager = new PubSubManager($app, $factory);

        $callable = Mockery::mock(\stdClass::class);
        $callable->shouldReceive('make');

        $manager->extend('custom_connection', [$callable, 'make']);

        $this->assertEmpty($manager->getConnections());
        $manager->connection('custom_connection');
        $connections = $manager->getConnections();
        $this->assertEquals(1, count($connections));
        $this->assertArrayHasKey('custom_connection', $connections);
    }

    public function testConnectionShouldResolveSubscribeConnectionToSubConfig()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn($this->getMockPubSubConfig());

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);
        $factory->shouldReceive('make')
            ->withArgs([
                'http',
                [
                    'uri' => 'http://127.0.0.1',
                    'subscribe_connection' => '/dev/null',
                    'subscribe_connection_config' => [
                        'driver' => '/dev/null',
                    ],
                ],
            ])
            ->once()
            ->andReturn(PubSubAdapterInterface::class);

        $manager = new PubSubManager($app, $factory);

        $manager->connection('http');
    }

    public function testGetDefaultConnection()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with('pubsub')
            ->once()
            ->andReturn([
                'default' => '/dev/null',
            ]);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);

        $manager = new PubSubManager($app, $factory);

        $this->assertEquals('/dev/null', $manager->getDefaultConnection());
    }

    public function testSetDefaultConnection()
    {
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('set')
            ->withArgs([
                'pubsub.default',
                '/dev/null',
            ])
            ->once();

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);

        $manager = new PubSubManager($app, $factory);

        $manager->setDefaultConnection('/dev/null');
    }

    public function testExtend()
    {
        $app = Mockery::mock(Application::class);

        $factory = Mockery::mock(PubSubConnectionFactory::class, [$app]);

        $manager = new PubSubManager($app, $factory);

        $extensions = $manager->getExtensions();
        $this->assertInternalType('array', $extensions);

        $callable = function () {
            //
        };
        $manager->extend('custom_connection', $callable);

        $extensions = $manager->getExtensions();

        $this->assertEquals(1, count($extensions));
        $this->assertArrayHasKey('custom_connection', $extensions);
        $this->assertSame($callable, $extensions['custom_connection']);
    }
}
