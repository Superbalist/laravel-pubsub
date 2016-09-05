<?php

namespace Superbalist\LaravelPubSub;

use Illuminate\Support\Facades\Facade;

class PubSubFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pubsub';
    }
}
