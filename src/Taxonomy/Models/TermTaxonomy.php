<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomy extends Model {

    protected $fillable = ['term_id', 'taxonomy', 'description', 'parent', 'count'];

}
