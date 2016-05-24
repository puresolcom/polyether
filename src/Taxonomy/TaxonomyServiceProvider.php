<?php

namespace Polyether\Taxonomy;

use Illuminate\Support\ServiceProvider;
use Polyether\Support\EtherError;
use View;

class TaxonomyServiceProvider extends ServiceProvider
{
    public function register()
    {

        $this->app->singleton('Taxonomy', function ($app) {
            return new Taxonomy($app->make(Repositories\TermRepository::class),
                $app->make(Repositories\TermTaxonomyRepository::class),
                $app->make(Repositories\TermTaxonomyRelationshipsRepository::class),
                $app->make(\Polyether\Post\Repositories\PostRepository::class));
        });
    }

    public function boot()
    {
        $this->termsAjaxHandler();
    }

    protected function termsAjaxHandler()
    {
        \Plugin::add_action('ajax_backend_get_taxonomy_terms', function () {
            $terms = $this->app->make(Repositories\TermTaxonomyRepository::class)
                               ->ajaxGetSelect2TaxonomyTerms(\Request::get('taxonomy'), \Request::get('term'),
                                   \Request::get('value'));

            return \Response::json($terms)->send();
        }, 0);

        \Plugin::add_action('ajax_backend_add_taxonomy_term', function () {
            $term = \Request::all();

            $response = [];

            if (count($term) > 1) {
                $termKeys = array_keys($term);
                $termValues = array_values($term);

                $taxName = str_replace('new', '', $termKeys[ 0 ]);
                $taxObj = $this->app->make('Taxonomy')->getTaxonomy($taxName);
                $termName = $termValues[ 0 ];
                $termParent = (0 != $termValues[ 1 ]) ? (int)$termValues[ 1 ] : null;
                $postId = isset($termValues[ 2 ]) ? $termValues[ 2 ] : 0;


                $createdTerm = $this->app->make('Taxonomy')->createTerm($termName, $taxName, ['parent' => $termParent]);
                if ($createdTerm && ! $createdTerm instanceof EtherError) {
                    $result[ 'replaces' ][ $taxName . '_parent_select' ] = View::make('backend::post.taxonomy-parent-select',
                        [
                            'taxName' => $taxName,
                            'taxObj'  => $taxObj,
                        ])->render();


                    $result[ 'replaces' ][ $taxName . '_checklist' ] = View::make('backend::post.taxonomy-checklist', [
                        'taxName' => $taxName,
                        'postId'  => $postId,
                        'taxObj'  => $taxObj,
                    ])->render();
                    $response[ 'success' ] = $result;
                } else {
                    $response[ 'error' ] = $response[ 'error' ] = $createdTerm->first();
                }

            } else {
                $response[ 'error' ] = 'Invalid Arguments Supplied';
            }

            return \Response::json($response)->send();

        }, 0);
    }

}
