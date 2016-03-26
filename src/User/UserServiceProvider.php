<?php

namespace Polyether\User;


use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('UserGate', UserGate::class);
    }

    public function boot()
    {
        $this->app->make('UserGate')->onBoot();
    }

}