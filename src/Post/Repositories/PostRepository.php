<?php

namespace Polyether\Post\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class PostRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Post\Models\Post::class;
    }

    public function allPosts ($args)
    {
        $columns = ['*'];
        if (isset($args[ 'columns' ]) && !empty($args[ 'columns' ]))
            $columns = $args[ 'columns' ];

        if (isset($args[ 'paginate' ]) && !empty($args[ 'paginate' ])) {
            return parent::paginate($args[ 'paginate' ], $columns);
        } else {
            return parent::all($columns);
        }
    }

}
