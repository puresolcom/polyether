<?php

namespace Polyether\Post\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model {

    protected $fillable = ['post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'post_slug', 'post_parent', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];

}
