<?php

namespace Superbalist\LaravelPubSub;

use Google\Cloud\PubSub\PubSubClient as GoogleCloudPubSubClient;
use InvalidArgumentException;
use Predis\Client as RedisClient;
use Superbalist\PubSub\Adapters\DevNullPubSubAdapter;
use Superbalist\PubSub\Adapters\LocalPubSubAdapter;
use Superbalist\PubSub\GoogleCloud\GoogleCloudPubSubAdapter;
use Superbalist\PubSub\Kafka\KafkaPubSubAdapter;
use Superbalist\PubSub\PubSubAdapterInterface;
use Superbalist\PubSub\Redis\RedisPubSubAdapter;

class PubSubConnectionFactory
{
    /**
     * Factory a PubSubAdapterInterface.
     *
     * @param string $driver
     * @param array $config
     * @return PubSubAdapterInterface
     */
    public function make($driver, array $config)
    {
        switch ($driver) {
            case 'null':
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

        $client = new RedisClient($config);

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
        $topicConfig = new \RdKafka\TopicConf();
        $topicConfig->set('auto.offset.reset', 'smallest');
        $topicConfig->set('auto.commit.interval.ms', 300);

        // create producer
        $producer = new \RdKafka\Producer();
        $producer->addBrokers($config['brokers']);

        // create consumer
        // see https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka.examples-high-level-consumer.html
        $consumerConfig = new \RdKafka\Conf();
        $consumerConfig->set('group.id', 'php-pubsub');

        $consumer = new \RdKafka\Consumer($consumerConfig);
        $consumer->addBrokers($config['brokers']);

        return new KafkaPubSubAdapter($producer, $consumer, $topicConfig);
    }

    /**
     * Factory a GoogleCloudPubSubAdapter.
     *
     * @param array $config
     * @return GoogleCloudPubSubAdapter
     */
    protected function makeGoogleCloudAdapter(array $config)
    {
        $client = new GoogleCloudPubSubClient([
            'projectId' => $config['project_id'],
            'keyFile' => $config['key_file'],
        ]);

        return new GoogleCloudPubSubAdapter($client);
    }
}
