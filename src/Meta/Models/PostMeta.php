<?php

namespace Polyether\Meta\Models;

use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{

    protected $fillable = ['post_id', 'meta_key', 'meta_value'];

}
