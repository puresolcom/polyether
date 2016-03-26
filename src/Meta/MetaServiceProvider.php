<?php

namespace Polyether\Meta;

use Illuminate\Support\ServiceProvider;
use Polyether\Meta\Repositories\PostMetaRepository;
use Polyether\Meta\Repositories\UserMetaRepository;

class MetaServiceProvider extends ServiceProvider
{

    protected $defer = true;

    public function register()
    {
        $this->app->singleton('Meta', function ($app) {
            return new MetaAPI($app->make(UserMetaRepository::class), $app->make(PostMetaRepository::class));
        });
    }

    public function provides()
    {
        return ['Meta'];
    }

}
