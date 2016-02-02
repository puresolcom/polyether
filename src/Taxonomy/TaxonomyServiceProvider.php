<?php

namespace Polyether\Taxonomy;

use Illuminate\Support\ServiceProvider;
use \Polyether\Taxonomy\Taxonomy;

class TaxonomyServiceProvider extends ServiceProvider {

    public function boot() {
        
    }

    public function register() {
        $this->app->singleton('Taxonomy', function($app) {
            return new Taxonomy($app->make('Polyether\App\Repositories\TermRepository'), $app->make('Polyether\App\Repositories\TermTaxonomyRepository'), $app->make('Polyether\App\Repositories\TermTaxonomyRelationshipsRepository'));
        });
    }

}
