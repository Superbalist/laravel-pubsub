<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Support\ServiceProvider;
use Superbalist\PubSub\PubSubAdapterInterface;

class PubSubServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/pubsub.php' => config_path('pubsub.php'),
        ]);
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pubsub.php', 'pubsub');

        $this->registerManager();
    }

    /**
     * Register the pub-sub manager.
     */
    public function registerManager()
    {
        $this->app->singleton('pubsub.factory', function () {
            return new PubSubConnectionFactory();
        });

        $this->app->singleton('pubsub', function ($app) {
            return new PubSubManager($app, $app['pubsub.factory']);
        });

        $this->app->bind('pubsub.connection', PubSubAdapterInterface::class);

        $this->app->bind(PubSubAdapterInterface::class, function ($app) {
            $manager = $app['pubsub']; /** @var PubSubManager $manager */
            return $manager->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'pubsub',
            'pubsub.factory',
            'pubsub.connection',
        ];
    }
}
