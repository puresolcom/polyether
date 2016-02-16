<?php

namespace Polyether\Taxonomy;

use Cache;
use Polyether\Support\EtherError;
use Polyether\Support\Traits\CacheHelperTrait;
use Polyether\Taxonomy\Repositories\TermRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRelationshipsRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRepository;

class Taxonomy
{

    use CacheHelperTrait;

    protected $termRepository;
    protected $termTaxonomyRepository;
    protected $termTaxonomyRelationShipsRepository;
    private $taxonomies = array();

    public function __construct (TermRepository $termRepository, TermTaxonomyRepository $termTaxonomyRepository, TermTaxonomyRelationshipsRepository $termTaxonomyRelationshipsRepository)
    {
        $this->termRepository = $termRepository;
        $this->termTaxonomyRepository = $termTaxonomyRepository;
        $this->termTaxonomyRelationShipsRepository = $termTaxonomyRelationshipsRepository;

        $this->registerDefaultTaxonomies();
    }

    public function registerDefaultTaxonomies ()
    {
        $this->registerTaxonomy('category', 'post', ['hierarchical' => true, '_built_in' => true]);
        $this->registerTaxonomy('post_tag', 'post', ['hierarchical' => false, '_built_in' => true]);
        $this->registerTaxonomy('nav_menu', 'post', ['labels' => ['name' => 'Navigation Menus', 'singular' => 'Navigation Menu'], 'hierarchical' => true, 'show_ui' => false, '_built_in' => true]);
    }

    /**
     * @param string       $taxonomy
     * @param string|array $object_type
     * @param array        $args
     *
     * @return \Polyether\Support\EtherError
     */
    public function registerTaxonomy ($taxonomy, $object_type, $args = array())
    {

        if (empty($taxonomy) || strlen($taxonomy) > 32)
            return new EtherError('Taxonomy names must be between 1 and 32 characters in length.');

        if (empty($object_type)) {
            return new EtherError('Object type can not be empty');
        }

        $defaults = [
            'labels'             => [],
            'show_ui'            => true,
            'show_in_admin_menu' => null,
            'show_in_nav_menu'   => null,
            'hierarchical'       => false,
            '_built_in'          => false];


        $args = array_merge($defaults, $args);


        if (null === $args[ 'show_in_admin_menu' ])
            $args[ 'show_in_admin_menu' ] = $args[ 'show_ui' ];

        if (null === $args[ 'show_in_nav_menu' ])
            $args[ 'show_in_nav_menu' ] = $args[ 'show_ui' ];

        $args[ 'object_type' ] = array_unique((array)$object_type);

        $this->taxonomies[ $taxonomy ] = (object)$args;
    }

    public function unregisterTaxonomy ($taxonomy)
    {
        if (isset($this->taxonomies[ $taxonomy ])) {
            unset($this->taxonomies[ $taxonomy ]);

            return true;
        }

        return false;
    }

    public function registerTaxonomyForObjectType ($taxonomy, $object_type)
    {
        if (!isset($this->taxonomies[ $taxonomy ]))
            return false;

        $new_object_type = (array)$object_type;

        $current_object_type = $this->taxonomies[ $taxonomy ]->object_type;

        $this->taxonomies[ $taxonomy ]->object_type = array_unique(array_merge($current_object_type, $new_object_type));
    }

    public function getTaxonomies ()
    {
        return $this->taxonomies;
    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param array      $args
     *
     * @return false|TermTaxonomy|\Polyether\Support\EtherError
     */
    public function createTerm ($term, $taxonomy, $args = [])
    {
        $defaults = [
            'description' => '',
            'parent'      => null,
            'slug'        => ''
        ];

        $args = array_merge($defaults, $args);


        if (!$this->taxonomyExists($taxonomy)) {
            return new EtherError('Invalid Taxonomy');
        }

        if (is_int($args[ 'parent' ])) {

            if (!$this->isTaxonomyHierarchical($taxonomy))
                return new EtherError('Taxonomy are not hierarchical, can\'t accept parent');

            if (!$this->termExists($args[ 'parent' ], $taxonomy))
                return new EtherError('Parent term id not found or not using the same taxonomy provided');
        }


        if (is_int($term)) {
            if ($term == 0)
                return new EtherError('Invalid term id');

            // we check if term id exists if so we can assign it to the taxonomy
            if (!$this->termExists($term))
                return new EtherError('Term id not found');

            if (!$this->termNotAssignedToAnyTaxonomy($term)) {
                return new EtherError('Term id already assigned to taxonomy');
            } else {
                $result = $this->termTaxonomyRepository->create([
                    'term_id'     => (int)$term,
                    'taxonomy'    => $taxonomy,
                    'description' => $args[ 'description' ],
                    'parent'      => $args[ 'parent' ]
                ]);
                if ($result) {
                    $this->flushCache();
                    return $result;
                } else {
                    return false;
                }
            }

        }


        if (is_string($term)) {
            if ($term == '')
                return new EtherError('Invalid term name');

            $termWithTaxonomyFound = $this->termExists($term, $taxonomy);

            if ($termWithTaxonomyFound && null != $args[ 'parent' ] && $args[ 'parent' ] == $termWithTaxonomyFound[ 'parent' ]) {
                return new EtherError('Term already assigned to the taxonomy');
            } else {
                if (($termOnlyFound = $this->termExists($term)) && $this->termNotAssignedToAnyTaxonomy((int)$termOnlyFound[ 'term_id' ])) {
                    return $this->createTerm((int)$termWithTaxonomyFound[ 'term_id' ], $taxonomy, $args);
                } else {

                    $slug = !empty($args[ 'slug' ]) ? $this->termRepository->createSlug($args[ 'slug' ]) : $this->termRepository->createSlug($term);

                    if ($termWithTaxonomyFound) {
                        $slug = $this->termRepository->sluggable($slug, 'slug');
                    }
                    $createdTerm = $this->termRepository->create([
                        'name' => $term,
                        'slug' => $slug
                    ]);
                    if ($createdTerm) {

                        $result = $this->termTaxonomyRepository->create([
                            'term_id'     => (int)$createdTerm->id,
                            'taxonomy'    => $taxonomy,
                            'description' => $args[ 'description' ],
                            'parent'      => $args[ 'parent' ]
                        ]);
                        if ($result) {
                            $this->flushCache();
                            return $result;
                        }

                        return false;
                    } else {
                        return false;
                    }
                }
            }
        }
    }

    public function taxonomyExists ($taxonomy)
    {
        return isset($this->taxonomies[ $taxonomy ]);
    }

    public function isTaxonomyHierarchical ($taxonomy)
    {
        if (!$this->taxonomyExists($taxonomy))
            return false;

        $taxonomy = $this->getTaxonomy($taxonomy);
        return $taxonomy->hierarchical;
    }

    public function getTaxonomy ($taxonomy)
    {

        if (!$this->taxonomyExists($taxonomy))
            return false;

        return $this->taxonomies[ $taxonomy ];
    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param null|int   $parent
     *
     * @return false|array
     */
    public function termExists ($term, $taxonomy = '', $parent = null)
    {
        if (is_int($term)) {
            if ($term == 0)
                return false;
            $query = $this->termRepository->getTermById($term, $taxonomy, $parent);
        }

        if (is_string($term)) {
            if ($term == '')
                return false;
            if (!empty($taxonomy)) {
                if (is_int($parent)) {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy($term, $taxonomy, $parent);
                } else {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy($term, $taxonomy);
                }
            } else {
                $query = $this->termRepository->getByNameOrSlug($term);
            }
        }
        return ($query) ? $query : false;

    }

    public function termNotAssignedToAnyTaxonomy ($term_id)
    {
        $query = $this->termTaxonomyRepository->findWhereFirst(['term_id' => $term_id], ['id'], true);
        return (empty($query)) ? true : false;
    }

    public function dropdownTerms ($args = [])
    {
        $defaults = [
            'show_options_all'  => '',
            'show_option_none'  => '',
            'option_none_value' => '',
            'orderby'           => 'id',
            'order'             => 'ASC',
            'with_post_counts'  => false,
            'hide_empty'        => false,
            'exclude'           => [],
            'echo'              => true,
            'hierarchical'      => false,
            'spacer'            => '&nbsp;&nbsp;',
            'name'              => 'cat',
            'id'                => '',
            'class'             => '',
            'selected'          => 0,
            'value_field'       => 'term_id',
            'taxonomy'          => 'category'
        ];

        $args = array_merge($defaults, $args);

        $cache_key = $this->setCacheKey('dropdown_terms_' . md5(http_build_query($args)));

        if (Cache::has($cache_key)) {
            $output = Cache::get($cache_key);
        } else {
            $taxonomy = $args[ 'taxonomy' ];
            $isHierarchical = $args[ 'hierarchical' ];
            $showOptionsAll = $args[ 'show_options_all' ];
            $showOptionNone = $args[ 'show_option_none' ];
            $optionNoneValue = $args[ 'option_none_value' ];
            $withPostCounts = $args[ 'with_post_counts' ];
            $echo = $args[ 'echo' ];
            $selectName = $args[ 'name' ];
            $selectId = $args[ 'id' ];
            $selectClass = $args[ 'class' ];
            $selectedValue = $args[ 'selected' ];
            $valueField = $args[ 'value_field' ];

            if ($isHierarchical) {
                $taxonomyTerms = $this->getHierarchicalTerms($taxonomy, null, $args);
            } else {
                $taxonomyTerms = $this->getTermsByTaxonomy($taxonomy, $args);
            }

            $output = "<select id=\"{$selectId}\" name=\"{$selectName}\" class=\"{$selectClass}\">";


            if (!empty($taxonomyTerms)) {

                $selected = ($selectedValue == 0) ? 'selected = "selected"' : '';

                if ($showOptionsAll) {
                    $output .= "<option {$selected} value=\"0\">{$showOptionsAll}</option>";
                }

                if (!$isHierarchical) {
                    foreach ($taxonomyTerms as $taxonomyTerm) {
                        $selected = ($selectedValue != 0 && $selectedValue == $taxonomyTerm[ $valueField ]) ? 'selected = "selected"' : '';
                        $postCounts = ($withPostCounts) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
                        $value = isset($taxonomyTerm[ $valueField ]) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
                        $output .= "<option $selected value=\"{$value}\">{$taxonomyTerm['term']['name']}{$postCounts}</option>";
                    }
                } else {
                    $output .= $this->_termsDropdownWalker($taxonomyTerms, $args);
                }
            } else {
                $output .= "<option value=\"{$optionNoneValue}\">{$showOptionNone}</option>";
            }

            $output .= '</select>';
        }

        if ($echo) {
            echo $output;
        } else {
            return $output;
        }
    }

    public function getHierarchicalTerms ($taxonomy, $parent = null, $args = [])
    {
        $cache_key = $this->setCacheKey('get_herarchical_terms_' . $taxonomy . '_' . $parent . '_' . md5(http_build_query($args)));

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        if ($this->taxonomyExists($taxonomy) && $this->isTaxonomyHierarchical($taxonomy)) {
            $taxonomyTerms = $this->getTermsByTaxonomy($taxonomy, $args);
            if (count($taxonomyTerms) > 0) {
                $result = $this->_buildTermsTree($taxonomyTerms, $parent);
                Cache::forever($cache_key, $result);
            } else {
                return;
            }
        } else {
            return;
        }

        return $result;
    }

    public function getTermsByTaxonomy ($taxonomy, $args = [])
    {


        $defaults = [
            'orderby'    => 'id',
            'order'      => 'ASC',
            'hide_empty' => false,
            'exclude'    => [],
        ];

        $args = array_merge($defaults, $args);

        $cache_key = $this->setCacheKey('get_terms_by_taxonomy_' . $taxonomy . '_' . md5(http_build_query($args)));

        if (Cache::has($cache_key))
            return Cache::get($cache_key);

        if ($this->taxonomyExists($taxonomy)) {
            $termTaxonomies = $this->termTaxonomyRepository->getTaxonomyTerms($taxonomy, $args);

            $result = null != $termTaxonomies ? $termTaxonomies : [];
        } else {
            $result = [];
        }

        return $result;
    }

    protected function _buildTermsTree (array $taxonomyTerms, $parent = null, $depth = 0)
    {
        $branch = [];

        if (count($taxonomyTerms) > 0) {
            foreach ($taxonomyTerms as $taxonomyTerm) {
                if ($parent == $taxonomyTerm[ 'parent' ]) {
                    $hasChildren = $this->_buildTermsTree($taxonomyTerms, $taxonomyTerm[ 'term_id' ], $depth + 1);
                    if ($hasChildren) {
                        $taxonomyTerm[ 'children' ] = $hasChildren;
                    }
                    $taxonomyTerm[ 'depth' ] = $depth;
                    $branch[] = $taxonomyTerm;
                }
            }
        }

        return $branch;
    }

    private function _termsDropdownWalker (array $taxonomyTerms, $args)
    {
        $spacer = $args[ 'spacer' ];
        $withPostCounts = $args[ 'with_post_counts' ];
        $selectedValue = $args[ 'selected' ];
        $valueField = $args[ 'value_field' ];
        $output = '';

        foreach ($taxonomyTerms as $taxonomyTerm) {
            $selected = ($selectedValue != 0 && $selectedValue == $taxonomyTerm[ $valueField ]) ? 'selected = "selected"' : '';
            $space = str_repeat($spacer, (int)$taxonomyTerm[ 'depth' ]);
            $postCounts = ($withPostCounts) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
            $value = isset($taxonomyTerm[ $valueField ]) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
            $output .= "<option $selected value=\"{$value}\">{$space} {$taxonomyTerm['term']['name']}{$postCounts}</option>";

            $hasChildren = (isset($taxonomyTerm[ 'children' ]) && !empty($taxonomyTerm[ 'children' ])) ? true : false;
            if ($hasChildren) {
                $output .= $this->_termsDropdownWalker($taxonomyTerm[ 'children' ], $args);
            }
        }

        return $output;
    }


}
