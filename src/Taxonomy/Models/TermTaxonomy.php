<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomy extends Model
{

    public $timestamps = false;
    protected $table = 'term_taxonomy';
    protected $fillable = ['term_id', 'taxonomy', 'description', 'parent', 'count'];

    public function term ()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

}
