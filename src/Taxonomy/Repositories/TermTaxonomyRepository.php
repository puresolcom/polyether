<?php

namespace Polyether\Taxonomy\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class TermTaxonomyRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Taxonomy\Models\TermTaxonomy::class;
    }

    public function getTermByNameOrSlug ($term)
    {
        return $this->model->term()->where('name', '=', $term)->orWhere('slug', '=', $term)->first();
    }

    public function getTaxonomyTerms ($taxonomy, array $args)
    {

        $orderBy = (isset($args[ 'orderby' ]) && !empty($args[ 'orderby' ])) ? $args[ 'orderby' ] : 'id';
        $order = (isset($args[ 'order' ]) && !empty($args[ 'order' ])) ? $args[ 'order' ] : 'ASC';

        $query = $this->model->where(function ($query) use ($taxonomy, $args) {
            $query->where('taxonomy', '=', $taxonomy);

            if ($args[ 'hide_empty' ])
                $query->where('count', '>', 0);

            if (!empty($args[ 'exclude' ]))
                $query->whereNotIn('term_id', $args[ 'exclude' ]);
        })->with('term')->orderBy($orderBy, $order)->get();
        return (null != ($query)) ? $query->toArray() : null;
    }

}
