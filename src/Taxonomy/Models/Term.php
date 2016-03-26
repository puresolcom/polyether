<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{

    public $timestamps = false;
    protected $fillable = ['name', 'slug'];
    protected $casts = ['id' => 'integer',];

    public function taxonomies()
    {
        return $this->hasMany(TermTaxonomy::class, 'term_id', 'id');
    }
}
