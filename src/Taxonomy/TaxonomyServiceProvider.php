<?php

namespace Polyether\Taxonomy;

use Illuminate\Support\ServiceProvider;

class TaxonomyServiceProvider extends ServiceProvider
{

    public function register ()
    {
        $this->app->singleton('Taxonomy', function ($app) {
            return new Taxonomy($app->make('Polyether\Taxonomy\Repositories\TermRepository'), $app->make('Polyether\Taxonomy\Repositories\TermTaxonomyRepository'), $app->make('Polyether\Taxonomy\Repositories\TermTaxonomyRelationshipsRepository'));
        });
    }

}
