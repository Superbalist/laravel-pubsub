<?php

namespace Superbalist\LaravelPubSub;

class PubSubLaravelServiceProvider extends PubSubBaseServiceProvider
{

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        Parent::register();

        $this->app->singleton('pubsub', function ($app) {
            return new PubSubManager($app, $app['pubsub.factory']);
        });
    }
}
