<?php

namespace Polyether\User;

use Illuminate\Support\Facades\Facade;

class UserGateFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'UserGate';
    }

}
