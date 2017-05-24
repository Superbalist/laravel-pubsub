<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Superbalist\PubSub\Adapters\DevNullPubSubAdapter;
use Superbalist\PubSub\Adapters\LocalPubSubAdapter;
use Superbalist\PubSub\GoogleCloud\GoogleCloudPubSubAdapter;
use Superbalist\PubSub\HTTP\HTTPPubSubAdapter;
use Superbalist\PubSub\Kafka\KafkaPubSubAdapter;
use Superbalist\PubSub\PubSubAdapterInterface;
use Superbalist\PubSub\Redis\RedisPubSubAdapter;

class PubSubConnectionFactory
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Factory a PubSubAdapterInterface.
     *
     * @param string $driver
     * @param array $config
     *
     * @return PubSubAdapterInterface
     */
    public function make($driver, array $config = [])
    {
        switch ($driver) {
            case '/dev/null':
                return new DevNullPubSubAdapter();
            case 'local':
                return new LocalPubSubAdapter();
            case 'redis':
                return $this->makeRedisAdapter($config);
            case 'kafka':
                return $this->makeKafkaAdapter($config);
            case 'gcloud':
                return $this->makeGoogleCloudAdapter($config);
            case 'http':
                return $this->makeHTTPAdapter($config);
        }

        throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver));
    }

    /**
     * Factory a RedisPubSubAdapter.
     *
     * @param array $config
     *
     * @return RedisPubSubAdapter
     */
    protected function makeRedisAdapter(array $config)
    {
        if (!isset($config['read_write_timeout'])) {
            $config['read_write_timeout'] = 0;
        }

        $client = $this->container->makeWith('pubsub.redis.redis_client', ['config' => $config]);

        return new RedisPubSubAdapter($client);
    }

    /**
     * Factory a KafkaPubSubAdapter.
     *
     * @param array $config
     *
     * @return KafkaPubSubAdapter
     */
    protected function makeKafkaAdapter(array $config)
    {
        // create producer
        $producer = $this->container->makeWith('pubsub.kafka.producer');
        $producer->addBrokers($config['brokers']);

        // create consumer
        $topicConf = $this->container->makeWith('pubsub.kafka.topic_conf');
        $topicConf->set('auto.offset.reset', 'smallest');

        $conf = $this->container->makeWith('pubsub.kafka.conf');
        $conf->set('group.id', array_get($config, 'consumer_group_id', 'php-pubsub'));
        $conf->set('metadata.broker.list', $config['brokers']);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('offset.store.method', 'broker');
        $conf->setDefaultTopicConf($topicConf);

        $consumer = $this->container->makeWith('pubsub.kafka.consumer', ['conf' => $conf]);

        return new KafkaPubSubAdapter($producer, $consumer);
    }

    /**
     * Factory a GoogleCloudPubSubAdapter.
     *
     * @param array $config
     *
     * @return GoogleCloudPubSubAdapter
     */
    protected function makeGoogleCloudAdapter(array $config)
    {
        $clientConfig = [
            'projectId' => $config['project_id'],
            'keyFilePath' => $config['key_file'],
        ];
        $client = $this->container->makeWith('pubsub.gcloud.pub_sub_client', ['config' => $clientConfig]);

        $clientIdentifier = array_get($config, 'client_identifier');
        $autoCreateTopics = array_get($config, 'auto_create_topics', true);
        $autoCreateSubscriptions = array_get($config, 'auto_create_subscriptions', true);

        return new GoogleCloudPubSubAdapter($client, $clientIdentifier, $autoCreateTopics, $autoCreateSubscriptions);
    }

    /**
     * Factory a HTTPPubSubAdapter.
     *
     * @param array $config
     *
     * @return HTTPPubSubAdapter
     */
    protected function makeHTTPAdapter(array $config)
    {
        $client = $this->container->make('pubsub.http.client');
        $adapter = $this->make(
            $config['subscribe_connection_config']['driver'],
            $config['subscribe_connection_config']
        );
        return new HTTPPubSubAdapter($client, $config['uri'], $adapter);
    }
}
