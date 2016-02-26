<?php

namespace Polyether\User\Repositories;

use App\User;
use Polyether\Support\EloquentRepository;

class UserRepository extends EloquentRepository
{
    public function model ()
    {
        return User::class;
    }

}