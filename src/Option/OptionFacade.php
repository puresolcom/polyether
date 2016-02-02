<?php

namespace Polyether\Option;

use Illuminate\Support\Facades\Facade;

class OptionFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'Option';
    }

}
