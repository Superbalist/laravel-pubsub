<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Contracts\Foundation\Application;
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
     * @return PubSubAdapterInterface
     */
    protected function makeConnection($name)
    {
        $config = $this->getConfig($name);

        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        $driver = $config['driver'];
        if (isset($this->extensions[$driver])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($driver, array_except($config, ['driver']));
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getConfig($name)
    {
        $connections = $this->app['config']['pubsub.connections'];
        if (!isset($connections[$name])) {
            throw new InvalidArgumentException(sprintf('The pub-sub connection [%s] is not configured.', $name));
        }

        $config = $connections[$name];

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException(
                sprintf('The pub-sub connection [%s] is missing a "driver" config var.', $name)
            );
        }

        return $config;
    }

    /**
     * Return the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['pubsub.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['pubsub.default'] = $name;
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
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
