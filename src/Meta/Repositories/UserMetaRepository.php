<?php

namespace Polyether\Meta\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class UserMetaRepository extends Repository
{

    public function model()
    {
        return \Polyether\Meta\Models\UserMeta::class;
    }

}
