<?php

namespace Polyether\Post\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class PostRepository extends Repository {

    public function model() {
        return \Polyether\Post\Models\Post::class;
    }

}
