<?php

namespace Polyether\Support\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for modular packages
 *
 * @author Mohammed Anwar <m.anwar@pure-sol.com>
 */
class ModuleServiceProvider extends ServiceProvider {

    protected $namespace;
    protected $middleware = [];
    protected $routeMiddleware = [];

    public function boot() {
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerBootComponents();
    }

    protected function registerMiddleware() {

        // Register middleware

        $router = $this->app[\Illuminate\Routing\Router::class];

        $httpKernel = $this->app[\Illuminate\Contracts\Http\Kernel::class];
        foreach ($this->middleware as $middleware) {
            $httpKernel->pushMiddleware($middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->middleware($key, $middleware);
        }
    }
    
    protected function registerBootComponenets() {
        
    }
    
    protected function registerConfig() {
        
    }
    
    protected function registerViews() {
        
    }
    
    protected function registerAssets() {
        
    }

    public function register() {
        
    }

}
