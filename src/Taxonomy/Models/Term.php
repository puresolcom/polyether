<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{

    public $timestamps = false;
    protected $fillable = ['name', 'slug'];

    public function taxonomy ()
    {
        return $this->hasOne(TermTaxonomy::class, 'term_id');
    }

}
