<?php

namespace Polyether\Post\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Polyether\Meta\Models\PostMeta;

class Post extends Model
{

    protected $fillable = ['post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'post_slug', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];

    public function author ()
    {
        return $this->belongsTo(User::class, 'post_author', 'id');
    }

    public function postMeta ()
    {
        return $this->hasMany(PostMeta::class, 'post_id', 'id');
    }
}
