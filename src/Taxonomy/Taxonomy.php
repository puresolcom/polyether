<?php

namespace Polyether\Taxonomy;

use Polyether\Taxonomy\Repositories\TermRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRelationshipsRepository;
use Polyether\Support\EtherError;
use Plugin;

class Taxonomy {

    private $taxonamies = array();
    protected $term;
    protected $termTaxonomy;
    protected $termTaxonomyRelationShips;

    public function __construct(TermRepository $term, TermTaxonomyRepository $termTaxonomy, TermTaxonomyRelationshipsRepository $termTaxonomyRelashionships) {
        $this->term = $term;
        $this->termTaxonomy = $termTaxonomy;
        $this->termTaxonomyRelationShips = $termTaxonomyRelashionships;
    }

    public function register_Taxonomy($Taxonomy, $object_type, $args = array()) {

        if (empty($Taxonomy) || strlen($Taxonomy) > 32)
            return new EtherError('Taxonomy names must be between 1 and 32 characters in length.');

        if (empty($object_type)) {
            return new EtherError('Object type can not be empty');
        }

        $defaults = [
            'permissions' => ['manage_categories', 'manage_posts'],
            'labels' => [
                'name' => 'Posts',
                'singular' => 'Post'
            ],
            'show_in_menu',
            'show_in_nav_menus',
            'hierarchial' => false,
            '_builtin' => false,
        ];

        $args = array_merge($defaults, $args);

        $args['object_type'] = array_unique((array) $object_type);

        $this->taxonamies[$Taxonomy] = (object) $args;
    }

    public function unregister_Taxonomy($Taxonomy) {
        if (isset($this->taxonamies[$Taxonomy])) {
            unset($this->taxonamies[$Taxonomy]);

            return true;
        }

        return false;
    }

    public function register_Taxonomy_for_object_type($Taxonomy, $object_type) {
        if (!isset($this->taxonamies[$Taxonomy]))
            return false;

        $new_object_type = (array) $object_type;

        $current_object_type = $this->taxonamies[$Taxonomy]->object_type;

        $this->taxonamies[$Taxonomy]->object_type = array_unique(array_merge($current_object_type, $new_object_type));
    }

    public function Taxonomy_exists($Taxonomy) {
        return isset($this->taxonamies[$Taxonomy]);
    }

    public function get_taxonamies() {
        return $this->taxonamies;
    }

}
