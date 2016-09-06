<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Superbalist\PubSub\Adapters\DevNullPubSubAdapter;
use Superbalist\PubSub\Adapters\LocalPubSubAdapter;
use Superbalist\PubSub\GoogleCloud\GoogleCloudPubSubAdapter;
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
        }

        throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver));
    }

    /**
     * Factory a RedisPubSubAdapter.
     *
     * @param array $config
     * @return RedisPubSubAdapter
     */
    protected function makeRedisAdapter(array $config)
    {
        if (!isset($config['read_write_timeout'])) {
            $config['read_write_timeout'] = 0;
        }

        $client = $this->container->make('pubsub.redis.redis_client', [$config]);

        return new RedisPubSubAdapter($client);
    }

    /**
     * Factory a KafkaPubSubAdapter.
     *
     * @param array $config
     * @return KafkaPubSubAdapter
     */
    protected function makeKafkaAdapter(array $config)
    {
        // use this topic config for both the producer and consumer
        $topicConf = $this->container->make('pubsub.kafka.topic_conf');
        $topicConf->set('auto.offset.reset', 'smallest');
        $topicConf->set('auto.commit.interval.ms', 300);

        // create producer
        $producer = $this->container->make('pubsub.kafka.producer');
        $producer->addBrokers($config['brokers']);

        // create consumer
        // see https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka.examples-high-level-consumer.html
        $consumer = $this->container->make('pubsub.kafka.consumer');
        $consumer->addBrokers($config['brokers']);

        return new KafkaPubSubAdapter($producer, $consumer, $topicConf);
    }

    /**
     * Factory a GoogleCloudPubSubAdapter.
     *
     * @param array $config
     * @return GoogleCloudPubSubAdapter
     */
    protected function makeGoogleCloudAdapter(array $config)
    {
        $clientConfig = [
            'projectId' => $config['project_id'],
            'keyFilePath' => $config['key_file'],
        ];
        $client = $this->container->make('pubsub.gcloud.pub_sub_client', [$clientConfig]);

        return new GoogleCloudPubSubAdapter($client);
    }
}
