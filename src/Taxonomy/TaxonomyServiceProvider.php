<?php

namespace Polyether\Taxonomy;

use Illuminate\Support\ServiceProvider;

class TaxonomyServiceProvider extends ServiceProvider
{
    public function register ()
    {

        $this->app->singleton('Taxonomy', function ($app) {
            return new Taxonomy($app->make(Repositories\TermRepository::class), $app->make(Repositories\TermTaxonomyRepository::class), $app->make(Repositories\TermTaxonomyRelationshipsRepository::class));
        });
    }

}
