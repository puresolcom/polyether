<?php

namespace Polyether\Support\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for modular packages
 *
 * @author Mohammed Anwar <m.anwar@pure-sol.com>
 */
abstract class ModuleServiceProvider extends ServiceProvider
{

    protected $namespace;
    protected $packageName;
    protected $packagePath;
    protected $configs = [];
    protected $middleware = [];
    protected $routeMiddleware = [];

    public function boot ()
    {
        $this->registerMiddleware();
        $this->loadRoutes();
        $this->registerBootComponents();
        $this->registerComponents();
    }

    protected function registerMiddleware ()
    {

        // Register middleware

        $router = $this->app[ \Illuminate\Routing\Router::class ];

        $httpKernel = $this->app[ \Illuminate\Contracts\Http\Kernel::class ];
        foreach ($this->middleware as $middleware) {
            $httpKernel->pushMiddleware($middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->middleware($key, $middleware);
        }
    }

    protected function loadRoutes ()
    {
        $routesPath = $this->packagePath . '/Http/routes.php';
        if ( ! $this->app->routesAreCached()) {
            if (file_exists($routesPath))
                require $routesPath;
        }
    }

    protected function registerBootComponents ()
    {
        $this->publishConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->publishAssets();
    }

    protected function registerComponents ()
    {
        $this->registerConfig();
    }

    protected function publishConfig ()
    {
        foreach ($this->configs as $config => $alias) {

            $this->publishes([
                                 $this->packagePath . 'config/' . $config . '.php' => config_path($config . '.php'),
                             ]);
        }
    }

    protected function registerViews ()
    {
        $this->loadViewsFrom($this->packagePath . 'resources/views', $this->packageName);

        $this->publishes([
                             $this->packagePath . 'resources/views' => resource_path('views/vendor/' . $this->packageName),
                         ]);
    }

    protected function registerTranslations ()
    {
        $this->loadTranslationsFrom($this->packagePath . 'resources/lang', $this->packageName);

        $this->publishes([
                             $this->packagePath . 'resources/lang' => resource_path('lang/vendor/' . $this->packageName),
                         ]);
    }

    protected function publishAssets ()
    {
        $this->publishes([
                             $this->packagePath . 'public' => public_path('vendor/' . $this->packageName),
                         ], 'public');
        $this->publishes([
                             $this->packagePath . 'resources' => resource_path('vendor/' . $this->packageName),
                         ]);
    }

    protected function registerConfig ()
    {
        foreach ($this->configs as $config => $alias) {
            $this->mergeConfigFrom(
                $this->packagePath . 'config/' . $config . '.php', $alias
            );
        }
    }

    public function register ()
    {
        $this->InitVars();
        $this->registerComponents();
    }

    protected abstract function InitVars ();

}
