<?php

namespace Polyether\Post\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Polyether\Meta\Models\PostMeta;
use Polyether\Taxonomy\Models\TermTaxonomy;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [ 'post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status',
                            'comment_status', 'post_slug', 'post_parent', 'guid', 'menu_order', 'post_type',
                            'post_mime_type', 'comment_count', 'created_at' ];

    protected $dates = [ 'created_at', 'updated_at', 'deleted_at' ];

    public function author()
    {
        return $this->belongsTo( User::class, 'post_author', 'id' );
    }

    public function postMeta()
    {
        return $this->hasMany( PostMeta::class, 'post_id', 'id' );
    }

    public function termTaxonomies()
    {
        return $this->belongsToMany( TermTaxonomy::class, 'term_relationships', 'object_id', 'term_taxonomy_id' );
    }
}
