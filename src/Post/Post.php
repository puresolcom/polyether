<?php

namespace Polyether\Post;

use Auth;
use Cache;
use Illuminate\Database\Eloquent\Collection;
use Option;
use Plugin;
use Polyether\Post\Repositories\PostRepository;
use Polyether\Support\EtherError;
use Request;
use Taxonomy;

/**
 * Class Post
 * @package Polyether\Post
 */
class Post
{

    /**
     * @var array
     */
    protected $postTypes = [];

    /**
     * @var \Polyether\Post\Repositories\PostRepository
     */
    protected $postRepository;

    /**
     * Post constructor.
     *
     * @param \Polyether\Post\Repositories\PostRepository $postRepository
     *
     * @return void
     */
    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;

        $this->registerDefaultPostTypes();

        $this->coreHookHandlers();

    }

    /**
     * Registering initial post types
     * @return void;
     */
    private function registerDefaultPostTypes()
    {
        $this->registerPostType('post', [
            'labels'        => ['name' => 'Posts', 'singular' => 'Post',],
            'hierarchical'  => false,
            'show_ui'       => true,
            'icon'          => 'fa fa-pencil',
            'menu_position' => 10,
            '_built_in'     => true,
        ]);

        $this->registerPostType('page', [
            'labels'        => ['name' => 'Pages', 'singular' => 'Page',],
            'hierarchical'  => true,
            'show_ui'       => true,
            'icon'          => 'fa fa-file',
            'menu_position' => 11,
            '_built_in'     => true,
        ]);
    }

    private function coreHookHandlers()
    {
        Plugin::add_action('post_status_changed', [$this, 'postStatusUpdated'], 1, 3);
    }

    /**
     * Register A Post Type
     *
     * @param string $post_type
     * @param array  $args
     *
     * @return void|EtherError
     */
    public function registerPostType($post_type, $args = array())
    {

        if ($this->postTypeObjectExists($post_type)) {
            return new EtherError('Post type with the same name already exists');
        }

        // Args prefixed with an underscore are reserved for internal use.
        $defaults = [
            'labels'             => ['name' => 'Posts', 'singular' => 'Post',],
            'description'        => '',
            'show_ui'            => true,
            'show_in_admin_menu' => null,
            'show_in_nav_menu'   => null,
            'icon'               => null,
            'hierarchical'       => false,
            'taxonomies'         => [],
            'permissions'        => [
                'view_posts'   => 'view_posts',
                'create_posts' => 'create_posts',
                'edit_posts'   => 'edit_posts',
                'delete_posts' => 'delete_posts',
            ],
            'menu_position'      => null,
            '_built_in'          => false,
        ];

        $args = array_merge($defaults, $args);

        if (null === $args[ 'show_in_admin_menu' ]) {
            $args[ 'show_in_admin_menu' ] = $args[ 'show_ui' ];
        }

        if (null === $args[ 'show_in_nav_menu' ]) {
            $args[ 'show_in_nav_menu' ] = $args[ 'show_ui' ];
        }


        $args = (object)$args;

        $args->name = $post_type;

        if (empty($post_type) || strlen($post_type) > 20) {
            return new EtherError('Post type must be less than 20 characters length');
        }

        foreach ($args->taxonomies as $taxonomy) {
            Taxonomy::registerTaxonomyForObjectType($taxonomy, $post_type);
        }

        $this->postTypes[ $post_type ] = $args;
    }

    /**
     * Check of post type object exists
     *
     * @param string $post_type
     *
     * @return bool
     */
    public function postTypeObjectExists($post_type)
    {
        return isset($this->postTypes[ $post_type ]);
    }

    /**
     * Returns the registered post type object
     *
     * @param string $post_type
     *
     * @return false|\stdClass
     */
    public function getPostTypeObject($post_type)
    {
        if ( ! isset($this->postTypes[ $post_type ])) {
            return false;
        }

        return $this->postTypes[ $post_type ];
    }

    /**
     * @return array
     */
    public function getPostTypes()
    {
        return $this->postTypes;
    }

    /**
     * Inserts a new post and return the post id
     *
     * @param array $postArr
     *
     * @return integer|EtherError|null
     */
    public function create($postArr)
    {
        $userId = null;
        if (Auth::check()) {
            $userId = Auth::user()->id;
        }

        $default = [
            'post_author'    => $userId,
            'post_content'   => '',
            'post_title'     => '',
            'post_excerpt'   => '',
            'post_status'    => 'draft',
            'post_type'      => 'post',
            'comment_status' => '',
            'post_parent'    => 0,
            'menu_order'     => 0,
        ];

        $postArr = array_unique(array_merge($default, $postArr));

        $postArr[ 'guid' ] = sha1(time());

        if (empty($postArr[ 'post_title' ])) {
            return new EtherError('Post title must be provided');
        }

        if (isset($postArr[ 'post_slug' ]) && ! empty($postArr[ 'post_slug' ])) {
            $postArr[ 'post_slug' ] = $this->postRepository->sluggable($postArr[ 'post_slug' ], 'post_slug');
        } else {
            $postArr[ 'post_slug' ] = $this->postRepository->sluggable($postArr[ 'post_title' ], 'post_slug');
        }
        try {
            $post = $this->postRepository->create($postArr);
        } catch (\Exception $e) {
            return new EtherError($e);
        }

        return $post;
    }

    /**
     * @param $postId
     * @param $postArr
     *
     * @return integer|EtherError|null
     */
    public function update($postId, $postArr)
    {

        if ( ! $postBeforeUpdate = $this->find($postId, ['id', 'post_status'])) {
            return false;
        }

        try {
            $post = $this->postRepository->update($postArr, $postId);
        } catch (\Exception $e) {
            return new EtherError($e);
        }

        if (isset($postArr[ 'post_status' ]) && $postArr[ 'post_status' ] != $post[ 'post_status' ]) {
            Plugin::do_action('post_status_changed', $postId, $postArr[ 'post_status' ],
                $postBeforeUpdate[ 'post_status' ]);
        }

        Cache::tags('post_' . $postId)->flush();

        return $post;
    }

    /**
     * Find a post object by id
     *
     * @param integer $postId
     *
     * @return \Illuminate\Support\Collection||null
     */
    public function find($postId, $columns = ['*'])
    {
        $cache_key = (is_array($columns) && $columns[ 0 ] == '*') ? 'post_' . $postId : 'post_' . md5($postId . http_build_query($columns));

        // See if we've the post cached earlier in this request and return it if it's available
        if (Cache::tags(['posts', 'post_' . $postId])->has($cache_key)) {
            return Cache::tags(['posts', 'post_' . $postId])->get($cache_key);
        } else {
            // Eventually we try to fetch the post from the database or return an error
            try {
                $post = $this->postRepository->findOrFail($postId, $columns);
                // Cache it using the caching system
                Cache::tags(['posts', 'post_' . $postId])
                     ->put($cache_key, $post, \Option::get('posts_cache_expires', 60));

                return $post;
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function delete($postId)
    {
        return $this->postRepository->delete($postId);
    }

    /**
     * @param array $args
     *
     * @return Collection|\Polyether\Support\EtherError
     */
    public function query($args = [])
    {
        $defaults = [
            'orderby'       => 'id',
            'order'         => 'DESC',
            'paginate'      => Option::get('default_posts_paginate', 20),
            'cat_in'        => [], //ids
            'cat_not_in'    => [], //ids
            'tag_in'        => [], //ids
            'tag_not_in'    => [], //ids
            'parent_in'     => [], //ids
            'parent_not_in' => [],
            'meta_query'    => [], // Array Of Arrays

        ];

        $args = array_merge($defaults, $args);

        $current_page = Request::get('page', 1);
        $cache_key = 'posts_' . md5(http_build_query($args) . '_' . $current_page);
        if (Cache::tags('posts')->has($cache_key)) {
            $posts = Cache::tags('posts')->get($cache_key);
        } else {
            $posts = $this->postRepository->queryPosts($args);

            if ( ! count($posts) > 0) {
                return new EtherError('No Posts were found');
            }

            Cache::tags('posts')->put($cache_key, $posts, \Option::get('posts_cache_expires', 60));

        }

        return $posts;
    }

    public function postStatusUpdated($postId, $newStatus, $oldStatus)
    {
        if ($newStatus != $oldStatus) {
            if ( ! empty($terms = $this->postRepository->getTermTaxonomies($postId)->toArray())) {
                $ttIds = array_pluck($terms, 'id');
                Taxonomy::updateTermCount($ttIds);
            }
        }
    }

}
