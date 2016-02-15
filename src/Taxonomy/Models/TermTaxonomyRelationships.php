<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomyRelationships extends Model
{

    public $timestamps = false;
    protected $fillable = ['object_id', 'term_taxonomy_id'];

}
