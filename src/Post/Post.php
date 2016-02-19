<?php

namespace Polyether\Post;

use Cache;
use Option;
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
    public function __construct (PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;

        $this->registerDefaultPostTypes();
    }

    /**
     * Registering initial post types
     * @return void;
     */
    public function registerDefaultPostTypes ()
    {
        $this->registerPostType('post', [
            'labels'       => [
                'name'     => 'Posts',
                'singular' => 'Post',
            ],
            'hierarchical' => false,
            '_built_in'    => true,
        ]);

        $this->registerPostType('page', [
            'labels'       => [
                'name'     => 'Pages',
                'singular' => 'Page',
            ],
            'hierarchical' => true,
            '_built_in'    => true,
        ]);
    }

    /**
     * Register A Post Type
     *
     * @param string $post_type
     * @param array  $args
     *
     * @return void|EtherError
     */
    public function registerPostType ($post_type, $args = array())
    {

        // Args prefixed with an underscore are reserved for internal use.
        $defaults = [
            'labels'             => [
                'name'     => 'Posts',
                'singular' => 'Post',
            ],
            'description'        => '',
            'show_ui'            => true,
            'show_in_admin_menu' => null,
            'show_in_nav_menu'   => null,
            'hierarchical'       => false,
            'taxonomies'         => [],
            '_built_in'          => false];

        $args = array_merge($defaults, $args);

        if (null === $args[ 'show_in_admin_menu' ])
            $args[ 'show_in_admin_menu' ] = $args[ 'show_ui' ];

        if (null === $args[ 'show_in_nav_menu' ])
            $args[ 'show_in_nav_menu' ] = $args[ 'show_ui' ];


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
    public function postTypeObjectExists ($post_type)
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
    public function getPostTypeObject ($post_type)
    {
        if ( ! isset($this->postTypes[ $post_type ]))
            return false;

        return $this->postTypes[ $post_type ];
    }

    /**
     * @return array
     */
    public function getPostTypes ()
    {
        return $this->postTypes;
    }

    /**
     * Inserts a new post and return the post id
     *
     * @param array $postArr
     *
     * @return integer|EtherError
     */
    public function create ($postArr)
    {

        $userId = 1;
        if (\Auth::check())
            $userId = \Auth::user()->id;

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
            'guid'           => sha1(time()),
        ];

        $postArr = array_unique(array_merge($default, $postArr));

        if (empty($postArr[ 'post_title' ]))
            return new EtherError('Post title must be provided');

        $postArr[ 'post_slug' ] = $this->postRepository->sluggable($postArr[ 'post_title' ], 'post_slug');

        try {
            $post = $this->postRepository->create($postArr);
        } catch (\Exception $e) {
            return new EtherError($e);
        }

        return $post->id;
    }

    /**
     * Find a post object by id
     *
     * @param integer $post_id
     *
     * @return EtherPost|EtherError
     */
    public function find ($post_id)
    {

        $cache_key = 'post_' . md5($post_id);

        // See if we've the post cached earlier in this request and return it if it's available
        if (Cache::has($cache_key)) {
            return Cache::get($cache_key);
        } else {
            // Eventually we try to fetch the post from the database or return an error
            try {
                $post = $this->postRepository->findOrFail($post_id);
                // Cache it using the caching system
                Cache::put($cache_key, $post, \Option::get('posts_cache_expires', 60));

                return $post;
            } catch (\Exception $e) {
                return new EtherError($e);
            }
        }
    }

    public function query ($args = [])
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

        $current_page = Request::get('page', 1);
        $cache_key = 'posts_' . md5(http_build_query($args) . '_' . $current_page);
        if (Cache::has($cache_key)) {
            $posts = Cache::get($cache_key);
        } else {
            $posts = $this->postRepository->queryPosts($args);

            if ( ! count($posts) > 0)
                return new EtherError('No Posts were found');

            Cache::put($cache_key, $posts, \Option::get('posts_cache_expires', 60));

        }

        return $posts;
    }

}
