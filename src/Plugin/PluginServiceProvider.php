<?php

namespace Polyether\Plugin;

use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider {

    public function boot() {
        
    }

    public function register() {
        $this->app->singleton('Plugin', function($app) {
            return new PluginAPI();
        });
    }

}
