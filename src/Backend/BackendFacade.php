<?php

namespace Polyether\Backend;

use Illuminate\Support\Facades\Facade;

class BackendFacade extends Facade
{

    protected static function getFacadeAccessor ()
    {
        return 'Backend';
    }

}
