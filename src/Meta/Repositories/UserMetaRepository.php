<?php

namespace Polyether\Meta\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class UserMetaRepository extends Repository
{

    public function model ()
    {
        \Polyether\Meta\Models\UserMeta::class;
    }

}
