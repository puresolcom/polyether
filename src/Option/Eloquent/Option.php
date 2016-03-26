<?php

namespace Polyether\Option\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{

    protected $fillable = ['option_name', 'option_value', 'autoload'];

}
