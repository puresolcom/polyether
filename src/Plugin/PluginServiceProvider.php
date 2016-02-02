<?php

namespace Polyether\Plugin;

use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider {

    public function boot() {

        // Pushing the plugin's middleware

        $httpKernel = $this->app['Illuminate\Contracts\Http\Kernel'];
        $httpKernel->pushMiddleware(Http\Middleware\Plugin::class);
    }

    public function register() {
        $this->app->singleton('Plugin', function($app) {
            return new PluginAPI();
        });
    }

}
