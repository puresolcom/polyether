<?php

namespace Polyether\Meta\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class PostMetaRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Meta\Models\PostMeta::class;
    }

}
