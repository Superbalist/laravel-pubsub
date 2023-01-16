<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Superbalist\PubSub\PubSubAdapterInterface;

class PubSubManager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var PubSubConnectionFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @param Application $app
     * @param PubSubConnectionFactory $factory
     */
    public function __construct(Application $app, PubSubConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Return a pub-sub adapter instance.
     *
     * @param string $name
     *
     * @return PubSubAdapterInterface
     */
    public function connection($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultConnection();
        }

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Make an instance of a pub-sub adapter interface.
     *
     * @param string $name
     *
     * @return PubSubAdapterInterface
     */
    protected function makeConnection($name)
    {
        $config = $this->getConnectionConfig($name);

        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException(
                sprintf('The pub-sub connection [%s] is missing a "driver" config var.', $name)
            );
        }

        return $this->factory->make($config['driver'], Arr::except($config, ['driver']));
    }

    /**
     * Return the pubsub config for the given connection.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConnectionConfig($name)
    {
        $connections = $this->getConfig()['connections'];
        if (!isset($connections[$name])) {
            throw new InvalidArgumentException(sprintf('The pub-sub connection [%s] is not configured.', $name));
        }

        $config = $connections[$name];

        if (isset($config['subscribe_connection'])) {
            $config['subscribe_connection_config'] = $this->getConnectionConfig($config['subscribe_connection']);
        }

        return $config;
    }

    /**
     * Return the pubsub config array.
     *
     * @return array
     */
    protected function getConfig()
    {
        $config = $this->app->make('config'); /* @var \Illuminate\Contracts\Config\Repository $config */
        return $config->get('pubsub');
    }

    /**
     * Return the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->getConfig()['default'];
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $config = $this->app->make('config'); /* @var \Illuminate\Contracts\Config\Repository $config */
        $config->set('pubsub.default', $name);
    }

    /**
     * Register an extension connection resolver.
     *
     * @param string $name
     * @param callable $resolver
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all registered extension connection resolvers.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Return all the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
