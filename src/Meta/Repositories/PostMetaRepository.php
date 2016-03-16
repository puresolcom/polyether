<?php

namespace Polyether\Meta\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class PostMetaRepository extends Repository
{

    public function model()
    {
        return \Polyether\Meta\Models\PostMeta::class;
    }

}
