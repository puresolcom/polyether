<?php

namespace Polyether\Backend;

use Polyether\Support\Providers\ModuleServiceProvider;

/**
 * BackendServiceProvider
 *
 * @author Mohammed Anwar <m.anwar@pure-sol.com>
 */
class BackendServiceProvider extends ModuleServiceProvider {

    public function boot() {
        parent::boot();
        $this->loadRoutes();
    }

    protected function loadRoutes() {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/Http/routes.php';
        }
    }

}
