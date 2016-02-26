<?php

namespace Polyether\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;
use Polyether\Post\Models\Post;

class TermTaxonomy extends Model
{

    public $timestamps = false;

    protected $table = 'term_taxonomy';

    protected $fillable = [ 'term_id', 'taxonomy', 'description', 'parent', 'count' ];

    protected $casts = [ 'id' => 'integer', 'term_id' => 'integer', ];

    public function terms ()
    {
        return $this->term();
    }

    public function term ()
    {
        return $this->belongsTo( Term::class, 'term_id', 'id' );
    }

    public function posts ()
    {
        return $this->belongsToMany( Post::class, 'term_relationships', 'term_taxonomy_id', 'object_id' );
    }
}
