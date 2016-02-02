<?php

namespace Polyether\Post;

use Polyether\App\Repositories\PostRepository;
use Polyether\App\Libraries\EtherError;
use Plugin;
use Taxonomy;

class Post {

    protected $post_types = array();
    protected $post;

    public function __construct(PostRepository $post) {
        $this->post = $post;
    }

    public function register_post_type($post_type, $args = array()) {

        // Args prefixed with an underscore are reserved for internal use.
        $defaults = array(
            'labels' => array(),
            'description' => '',
            'hierarchical' => false,
            'show_in_menu' => null,
            'show_in_nav_menus' => null,
            'taxonamies' => [],
            'capabilities' => array(),
            '_builtin' => false
        );
        $args = array_merge($defaults, $args);
        $args = (object) $args;

        $post_type = sanitize_key($post_type);
        $args->name = $post_type;

        if (empty($args->capabilities)) {
            $args->capabilities = ['manage_posts'];
        }

        if (empty($post_type) || strlen($post_type) > 20) {
            return new EtherError('Post type must be less than 20 characters length');
        }

        foreach ($args->taxonomies as $taxonomy) {
            Taxonomy::register_taxonomy_for_object_type($taxonomy, $post_type);
        }

        $this->post_types[$post_type] = $args;
    }

    public function post_type_object_exists($post_type) {
        return isset($this->post_types[$post_type]);
    }

    public function get_post_type_object($post_type) {
        if (!isset($this->post_types[$post_type]))
            return false;

        return $this->post_types[$post_type];
    }

}
