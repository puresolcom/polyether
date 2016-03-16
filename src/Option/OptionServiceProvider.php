<?php

namespace Polyether\Option;

use Illuminate\Support\ServiceProvider;

class OptionServiceProvider extends ServiceProvider
{

    protected $defer = true;

    public function register()
    {
        $this->app->singleton( 'Option', function( $app ) {
            return new OptionAPI( $app->make( 'Polyether\Option\Repositories\OptionRepository' ) );
        } );
    }

    public function provides()
    {
        return [ 'Option' ];
    }

}
