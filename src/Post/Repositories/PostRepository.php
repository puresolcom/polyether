<?php

namespace Polyether\Post\Repositories;

use Polyether\Meta\Repositories\MetaQuery;
use Polyether\Support\EloquentRepository as Repository;

class PostRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Post\Models\Post::class;
    }

    /**
     * @param null|array $args
     */
    public function queryPosts ( $args = null )
    {
        $model = $this->model;

        if ( isset( $args[ 'user_meta_query' ] ) || isset( $args[ 'post_meta_query' ] ) )
            $metaQuery = new MetaQuery();

        if ( isset( $args[ 'user_meta_query' ] ) )
            $model = $metaQuery->whereHasUserMeta( $args[ 'user_meta_query' ], $model );
        if ( isset( $args[ 'post_meta_query' ] ) )
            $model = $metaQuery->whereHasPostMeta( $args[ 'post_meta_query' ], $model );

        return $model->get();

    }


    public function syncTermTaxonomies ( $objectId, $termTaxonomyIds )
    {
        if ( ! $model = $this->model->find( $objectId, [ 'id' ] ) )
            return false;

        if ( ! is_array( $termTaxonomyIds ) )
            $termTaxonomyIds = (array)$termTaxonomyIds;

        return $model->termTaxonomies()->sync( $termTaxonomyIds );
    }

    public function getTermTaxonomies ( $postId )
    {
        if ( ! $model = $this->model->select( 'id' )->find( $postId ) )
            return false;

        return $model->termTaxonomies()->get();
    }

    public function getPostTerms ( $postId, $taxonomy = null )
    {
        $model = $this->model->select( 'id' )->find( $postId )->termTaxonomies()->select( 'term_id' );

        if ( is_string( $taxonomy ) )
            $model = $model->where( 'taxonomy', '=', $taxonomy );

        $model = $model->with( 'terms' )->get()->pluck( 'terms' );

        if ( ! $model )
            return false;

        return $model;
    }

    public function removePostTerms ( $postId, $termIds, $taxonomy = null )
    {
        if ( ! $model = $this->model->find( $postId, [ 'id' ] ) )
            return false;

        if ( ! is_array( $termIds ) )
            $termIds = (array)$termIds;

        $ttIds = $model->termTaxonomies()->whereIn( 'term_id', $termIds )->get( [ 'term_taxonomy.id',
                                                                                  'term_id' ] )->pluck( 'id' )->toArray();

        if ( ! empty( $ttIds ) )
            $detach = $model->termTaxonomies()->detach( $ttIds ); else
            return 0;

        return $detach;

    }

}
