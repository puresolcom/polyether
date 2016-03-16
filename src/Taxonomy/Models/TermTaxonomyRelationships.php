<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomyRelationships extends Model
{

    public $timestamps = false;
    protected $table = 'term_relationships';
    protected $fillable = [ 'object_id', 'term_taxonomy_id' ];

    public function termTaxonomies()
    {
        return $this->belongsTo( TermTaxonomy::class, 'term_taxonomy_id', 'id' );
    }
}
