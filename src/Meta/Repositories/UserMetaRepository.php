<?php

namespace Polyether\Meta\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class UserMetaRepository extends Repository
{

    public function model()
    {
        \Polyether\Meta\Models\UserMeta::class;
    }

}
