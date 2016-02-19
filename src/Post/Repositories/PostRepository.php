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
    public function queryPosts ($args = null)
    {
        $model = $this->model;

        if (isset($args[ 'user_meta_query' ]) || isset($args[ 'post_meta_query' ]))
            $metaQuery = new MetaQuery();

        if (isset($args[ 'user_meta_query' ])) $model = $metaQuery->whereHasUserMeta($args[ 'user_meta_query' ], $model);
        if (isset($args[ 'post_meta_query' ])) $model = $metaQuery->whereHasPostMeta($args[ 'post_meta_query' ], $model);


        var_dump($model->get()->toArray());

    }

}
