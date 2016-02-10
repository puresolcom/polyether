<?php

namespace Polyether\Taxonomy;

use Polyether\Support\EtherError;
use Polyether\Taxonomy\Repositories\TermRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRelationshipsRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRepository;

class Taxonomy
{

    protected $term;
    protected $termTaxonomy;
    protected $termTaxonomyRelationShips;
    private $taxonomies = array();

    public function __construct (TermRepository $term, TermTaxonomyRepository $termTaxonomy, TermTaxonomyRelationshipsRepository $termTaxonomyRelationships)
    {
        $this->term = $term;
        $this->termTaxonomy = $termTaxonomy;
        $this->termTaxonomyRelationShips = $termTaxonomyRelationships;
    }

    public function register_taxonomy ($taxonomy, $object_type, $args = array())
    {

        if (empty($taxonomy) || strlen($taxonomy) > 32)
            return new EtherError('Taxonomy names must be between 1 and 32 characters in length.');

        if (empty($object_type)) {
            return new EtherError('Object type can not be empty');
        }

        $defaults = [
            'permissions'  => ['manage_categories', 'manage_posts'],
            'labels'       => [
                'name'     => 'Posts',
                'singular' => 'Post'
            ],
            'show_in_menu',
            'show_in_nav_menus',
            'hierarchical' => false,
            '_builtin'     => false,
        ];

        $args = array_merge($defaults, $args);

        $args[ 'object_type' ] = array_unique((array)$object_type);

        $this->taxonomies[ $taxonomy ] = (object)$args;
    }

    public function unregister_taxonomy ($taxonomy)
    {
        if (isset($this->taxonomies[ $taxonomy ])) {
            unset($this->taxonomies[ $taxonomy ]);

            return true;
        }

        return false;
    }

    public function register_taxonomy_for_object_type ($taxonomy, $object_type)
    {
        if (!isset($this->taxonomies[ $taxonomy ]))
            return false;

        $new_object_type = (array)$object_type;

        $current_object_type = $this->taxonomies[ $taxonomy ]->object_type;

        $this->taxonomies[ $taxonomy ]->object_type = array_unique(array_merge($current_object_type, $new_object_type));
    }

    public function taxonomy_exists ($taxonomy)
    {
        return isset($this->taxonomies[ $taxonomy ]);
    }

    public function get_taxonomies ()
    {
        return $this->taxonomies;
    }

}
