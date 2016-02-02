<?php

namespace Polyether\Option;

use Illuminate\Support\ServiceProvider;

class OptionServiceProvider extends ServiceProvider {

    public function boot() {
        
    }

    public function register() {
        $this->app->singleton('Option', function($app) {
            return new OptionAPI($app->make('Polyether\App\Repositories\OptionRepository'));
        });
    }

}
