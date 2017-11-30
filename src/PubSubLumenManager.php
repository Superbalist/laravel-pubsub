<?php

namespace Superbalist\LaravelPubSub;

use Laravel\Lumen\Application;

class PubSubLumenManager extends PubSubManager
{

    /**
     * @param Application $app
     * @param PubSubConnectionFactory $factory
     */
    public function __construct(Application $app, PubSubConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }
}
