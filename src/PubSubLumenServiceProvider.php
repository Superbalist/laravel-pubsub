<?php

namespace Superbalist\LaravelPubSub;

class PubSubLumenServiceProvider extends PubSubBaseServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->app->configure('pubsub');
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        Parent::register();

        $this->app->singleton('pubsub', function ($app) {
            return new PubSubLumenManager($app, $app['pubsub.factory']);
        });
    }
}
