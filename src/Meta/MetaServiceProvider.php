<?php

namespace Polyether\Meta;

use Illuminate\Support\ServiceProvider;

class MetaServiceProvider extends ServiceProvider
{

    public function boot ()
    {

    }

    public function register ()
    {
        $this->app->singleton('Meta', function ($app) {
            return new MetaAPI($app->make('Polyether\Meta\Repositories\UserMetaRepository'), $app->make('Polyether\Meta\Repositories\PostMetaRepository'));
        });
    }

}
