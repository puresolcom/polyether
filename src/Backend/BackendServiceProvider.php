<?php

namespace Polyether\Backend;

use Polyether\Support\Providers\ModuleServiceProvider;

/**
 * BackendServiceProvider
 *
 * @author Mohammed Anwar <m.anwar@pure-sol.com>
 */
class BackendServiceProvider extends ModuleServiceProvider
{
    protected $publishViews = false;

    public function register()
    {
        parent::register();
        $this->app->singleton('Backend', Backend::class);
    }

    public function boot()
    {
        parent::boot();
        $this->app->make('Backend')->onBoot();
    }

    protected function InitVars()
    {
        $this->namespace = __NAMESPACE__;
        $this->packagePath = __DIR__ . DIRECTORY_SEPARATOR;
        $this->packageName = 'backend';
        $this->configs = ['auth' => 'auth',];
    }

}
