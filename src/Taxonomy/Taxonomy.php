<?php

namespace Polyether\Taxonomy;

use Cache;
use Illuminate\Support\Collection;
use Polyether\Post\Repositories\PostRepository;
use Polyether\Support\EtherError;
use Polyether\Taxonomy\Repositories\TermRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRelationshipsRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRepository;

class Taxonomy
{

    protected $termRepository;
    protected $termTaxonomyRepository;
    protected $termTaxonomyRelationShipsRepository;
    protected $objectRepository;
    private $taxonomies = array();

    public function __construct(
        TermRepository $termRepository,
        TermTaxonomyRepository $termTaxonomyRepository,
        TermTaxonomyRelationshipsRepository $termTaxonomyRelationshipsRepository,
        PostRepository $objectRepository
    ) {
        $this->termRepository = $termRepository;
        $this->termTaxonomyRepository = $termTaxonomyRepository;
        $this->termTaxonomyRelationShipsRepository = $termTaxonomyRelationshipsRepository;
        $this->objectRepository = $objectRepository;

        $this->registerDefaultTaxonomies();
    }

    public function registerDefaultTaxonomies()
    {
        $this->registerTaxonomy('category', 'post', [
            'labels'       => [
                'name'     => 'Categories',
                'singular' => 'Category',
            ],
            'hierarchical' => true,
            '_built_in'    => true,
        ]);

        $this->registerTaxonomy('post_tag', 'post', [
            'labels'             => [
                'name'     => 'Tags',
                'singular' => 'Tag',
            ],
            'hierarchical'       => false,
            '_built_in'          => true,
            'show_ui'            => true,
            'show_in_admin_menu' => 'false',
            'show_in_nav_menu'   => false,
        ]);

        $this->registerTaxonomy('nav_menu', ['post', 'page'], [
            'labels'       => [
                'name'     => 'Navigation Menus',
                'singular' => 'Navigation Menu',
            ],
            'hierarchical' => true,
            'show_ui'      => false,
            '_built_in'    => true,
        ]);
    }

    /**
     * @param string       $taxonomy
     * @param string|array $object_type
     * @param array        $args
     *
     * @return \Polyether\Support\EtherError
     */
    public function registerTaxonomy($taxonomy, $object_type, $args = array())
    {

        if (empty($taxonomy) || strlen($taxonomy) > 32) {
            return new EtherError('Taxonomy names must be between 1 and 32 characters in length.');
        }

        if (empty($object_type)) {
            return new EtherError('Object type can not be empty');
        }

        $defaults = [
            'labels'             => [],
            'show_ui'            => true,
            'show_in_admin_menu' => null,
            'show_in_nav_menu'   => null,
            'hierarchical'       => false,
            '_built_in'          => false,
        ];


        $args = array_merge($defaults, $args);


        if (null === $args[ 'show_in_admin_menu' ]) {
            $args[ 'show_in_admin_menu' ] = $args[ 'show_ui' ];
        }

        if (null === $args[ 'show_in_nav_menu' ]) {
            $args[ 'show_in_nav_menu' ] = $args[ 'show_ui' ];
        }

        $args[ 'name' ] = $taxonomy;
        $args[ 'object_type' ] = array_unique((array)$object_type);

        $this->taxonomies[ $taxonomy ] = (object)$args;
    }

    public function unregisterTaxonomy($taxonomy)
    {
        if (isset($this->taxonomies[ $taxonomy ])) {
            unset($this->taxonomies[ $taxonomy ]);

            return true;
        }

        return false;
    }

    public function registerTaxonomyForObjectType($taxonomy, $object_type)
    {
        if ( ! isset($this->taxonomies[ $taxonomy ])) {
            return false;
        }

        $new_object_type = (array)$object_type;

        $current_object_type = $this->taxonomies[ $taxonomy ]->object_type;

        $this->taxonomies[ $taxonomy ]->object_type = array_unique(array_merge($current_object_type, $new_object_type));
    }

    public function getObjectTaxonomies($object, $output = 'names')
    {
        $object = degrade($object);

        $taxonomies = array();
        foreach ((array)$this->getTaxonomies() as $taxName => $taxObj) {
            if (array_intersect($object, (array)$taxObj->object_type)) {
                if ('names' == $output) {
                    $taxonomies[] = $taxName;
                } else {
                    $taxonomies[ $taxName ] = $taxObj;
                }
            }
        }

        return $taxonomies;
    }

    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    public function UITerms($args = [])
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
            'name'              => '',
            'id'                => '',
            'class'             => '',
            'selected'          => 0,
            'value_field'       => 'term_id',
            'taxonomy'          => 'category',
            'type'              => 'default',
        ];

        $args = array_merge($defaults, $args);

        $echo = $args[ 'echo' ];

        $type = $args[ 'type' ];

        $cacheKey = 'taxonomy_ui_terms_' . md5(http_build_query($args));

        if (Cache::tags(['taxonomies', 'taxonomy_ui_terms'])->has($cacheKey)) {
            $output = Cache::tags(['taxonomies', 'taxonomy_ui_terms'])->get($cacheKey);
        } else {
            $taxonomy = $args[ 'taxonomy' ];
            $isHierarchical = $args[ 'hierarchical' ];
            $showOptionsAll = $args[ 'show_options_all' ];
            $showOptionNone = $args[ 'show_option_none' ];
            $optionNoneValue = $args[ 'option_none_value' ];
            $name = $args[ 'name' ];
            $id = $args[ 'id' ];
            $class = $args[ 'class' ];
            $selected = $args[ 'selected' ];
            $valueField = $args[ 'value_field' ];

            if ($isHierarchical) {
                $taxonomyTerms = $this->getHierarchicalTerms($taxonomy, null, $args);
            } else {
                $taxonomyTerms = $this->getTermsByTaxonomy($taxonomy, $args);
            }
            if ('default' === $type) {
                if (true == $isHierarchical) {
                    $output = "<div  class=\"hierarchical-taxonomy-wrapper {$class}\"><input type=\"hidden\" name=\"{$name}\" value='0' ><ul class='taxonomy-list'>";

                    if ( ! empty($taxonomyTerms)) {

                        $selected = ($selected == 0) ? 'checked = "checked"' : '';

                        if ($showOptionsAll) {
                            $output .= "<li>
                                        <div class=\"checkbox\">
                                            <label>
                                            <input type=\"checkbox\" {$selected} name=\"taxonomy[{$taxonomy}][]\" value=\"{$valueField}\">
                                            {$showOptionsAll}
                                            </label>
                                        </div>
                                    </li>";
                        }

                        $output .= $this->_termsCheckboxWalker($taxonomyTerms, $args);

                    } else {
                        $output .= "<li>{$showOptionNone}</li>";
                    }

                    $output .= '</ul></div>';
                } else {

                    $output = "<input type=\"hidden\" name=\"{$name}\" value='0' ><select id=\"{$id}\" name=\"{$name}\" class=\"taxonomy-tag-select {$class}\" data-taxonomy=\"{$taxonomy}\" data-value-field=\"{$valueField}\" multiple=\"multiple\">";


                    if ( ! empty($taxonomyTerms)) {

                        $selected = ($selected == 0) ? 'selected = "selected"' : '';

                        if ($showOptionsAll) {
                            $output .= "<option {$selected} value=\"0\">{$showOptionsAll}</option>";
                        }
                        $output .= $this->_termsDropdownWalker($taxonomyTerms, $args);
                    } else {
                        $output .= "<option value=\"{$optionNoneValue}\">{$showOptionNone}</option>";
                    }

                    $output .= '</select>';
                }
            } else {
                $output = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\" data-taxonomy=\"{$taxonomy}\" data-value-field=\"{$valueField}\">";


                if ( ! empty($taxonomyTerms)) {

                    $selected = ($selected == 0) ? 'selected = "selected"' : '';

                    if ($showOptionsAll) {
                        $output .= "<option {$selected} value=\"0\">{$showOptionsAll}</option>";
                    }
                    $output .= $this->_termsDropdownWalker($taxonomyTerms, $args);
                } else {
                    $output .= "<option value=\"{$optionNoneValue}\">{$showOptionNone}</option>";
                }

                $output .= '</select>';
            }
            Cache::tags(['taxonomies', 'taxonomy_ui_terms'])->forever($cacheKey, $output);
        }

        if ($echo) {
            echo $output;
        } else {
            return $output;
        }
    }

    public function getHierarchicalTerms($taxonomy, $parent = null, $args = [])
    {
        $cacheKey = 'get_hierarchical_terms_' . $taxonomy . '_' . $parent . '_' . md5(http_build_query($args));
        $result = [];

        if (Cache::tags(['taxonomies', 'get_hierarchical_terms'])->has($cacheKey)) {
            return Cache::tags(['taxonomies', 'get_hierarchical_terms'])->get($cacheKey);
        }

        if ($this->taxonomyExists($taxonomy) && $this->isTaxonomyHierarchical($taxonomy)) {
            $taxonomyTerms = $this->getTermsByTaxonomy($taxonomy, $args);
            if (count($taxonomyTerms) > 0) {
                $result = $this->_buildTermsTree($taxonomyTerms, $parent);
                Cache::tags(['taxonomies', 'get_hierarchical_terms'])->forever($cacheKey, $result);
            }
        }

        return $result;
    }

    public function getTermsByTaxonomy($taxonomy, $args = [])
    {
        $defaults = ['orderby' => 'id', 'order' => 'ASC', 'hide_empty' => false, 'exclude' => [], 'limit' => null];
        $args = array_merge($defaults, $args);
        $result = [];
        $cacheKey = 'get_terms_by_taxonomy_' . $taxonomy . '_' . md5(http_build_query($args));

        if (Cache::tags(['taxonomies', 'get_terms_by_taxonomy'])->has($cacheKey)) {
            return Cache::tags(['taxonomies', 'get_terms_by_taxonomy'])->get($cacheKey);
        }

        if ($this->taxonomyExists($taxonomy)) {
            $termTaxonomies = $this->termTaxonomyRepository->getTaxonomyWithTerms($taxonomy, $args);

            if ($termTaxonomies) {
                Cache::tags(['taxonomies', 'get_terms_by_taxonomy'])->forever($cacheKey, $termTaxonomies);
                $result = $termTaxonomies;
            }
        }

        return $result;
    }

    private function _termsCheckboxWalker($taxonomyTerms, $args)
    {
        $taxonomy = $args[ 'taxonomy' ];
        $withPostCounts = $args[ 'with_post_counts' ];
        $selectedValues = (array)$args[ 'selected' ];
        $valueField = $args[ 'value_field' ];
        $name = $args[ 'name' ];
        $output = '';

        $taxonomyTerms = degrade($taxonomyTerms);

        foreach ($taxonomyTerms as $taxonomyTerm) {
            $selected = ( ! empty($selectedValues) && in_array($taxonomyTerm[ $valueField ],
                    $selectedValues)) ? 'checked = "checked"' : '';
            $postCounts = ($withPostCounts) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
            $value = isset($taxonomyTerm[ $valueField ]) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
            $output .= "<li>
                            <div class=\"checkbox\">
                                <label>
                                <input type=\"checkbox\" {$selected} value=\"{$value}\" name=\"{$name}\">
                                {$taxonomyTerm['term']['name']}{$postCounts}
                                </label>
                            </div>
                            ";
            $hasChildren = (isset($taxonomyTerm[ 'children' ]) && ! empty($taxonomyTerm[ 'children' ])) ? true : false;
            if ( ! empty($hasChildren)) {
                $output .= '<ul>';
                $output .= $this->_termsCheckboxWalker($taxonomyTerm[ 'children' ], $args);
                $output .= '</ul>';
                $output .= '</li>';
            } else {
                $output .= '</li>';
            }
        }

        return $output;
    }

    private function _termsDropdownWalker($taxonomyTerms, $args)
    {
        $isHierarchical = $args[ 'hierarchical' ];
        $withPostCounts = $args[ 'with_post_counts' ];
        $selectedValues = (array)$args[ 'selected' ];
        $valueField = $args[ 'value_field' ];
        $type = $args[ 'type' ];
        $output = '';

        $taxonomyTerms = degrade($taxonomyTerms);

        foreach ($taxonomyTerms as $taxonomyTerm) {
            $selected = ( ! empty($selectedValues) && in_array((int)$taxonomyTerm[ 'term' ] [ 'id' ],
                    $selectedValues)) ? 'selected = "selected"' : '';
            if ('default' == $type) {
                if ( ! empty($selected)) {
                    $postCounts = ($withPostCounts) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
                    $value = isset($taxonomyTerm[ $valueField ]) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
                    $output .= "<option {$selected} value=\"{$value}\">{$taxonomyTerm['term']['name']}{$postCounts}</option>\n";
                    $hasChildren = (isset($taxonomyTerm[ 'children' ]) && ! empty($taxonomyTerm[ 'children' ])) ? true : false;
                    if ($hasChildren) {
                        $output .= $this->_termsDropdownWalker($taxonomyTerm[ 'children' ], $args);
                    }
                }
            } else {
                $postCounts = ($withPostCounts) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
                $value = isset($taxonomyTerm[ $valueField ]) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
                $space = '';

                if ($isHierarchical) {
                    $spacer = $args[ 'spacer' ];
                    $space = str_repeat($spacer, (int)$taxonomyTerm[ 'depth' ]);
                }

                $output .= "<option {$selected} value=\"{$value}\">{$space}{$taxonomyTerm['term']['name']}{$postCounts}</option>\n";
                $hasChildren = (isset($taxonomyTerm[ 'children' ]) && ! empty($taxonomyTerm[ 'children' ])) ? true : false;
                if ($hasChildren) {
                    $output .= $this->_termsDropdownWalker($taxonomyTerm[ 'children' ], $args);
                }
            }
        }

        return $output;
    }

    public function taxonomyExists($taxonomy)
    {
        if ( ! is_string($taxonomy)) {
            return false;
        }

        return isset($this->taxonomies[ $taxonomy ]);
    }

    public function isTaxonomyHierarchical($taxonomy)
    {
        if ( ! $this->taxonomyExists($taxonomy)) {
            return false;
        }

        $taxonomy = $this->getTaxonomy($taxonomy);

        return $taxonomy->hierarchical;
    }

    protected function _buildTermsTree($taxonomyTerms, $parent = null, $depth = 0)
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

    public function getTaxonomy($taxonomy)
    {

        if ( ! $this->taxonomyExists($taxonomy)) {
            return false;
        }

        return $this->taxonomies[ $taxonomy ];
    }

    public function getObjectTermTaxonomiesByTermIds($objectId, $taxonomy, $termsIds)
    {
        return $this->objectRepository->getTermTaxonomies($objectId, $taxonomy, $termsIds);
    }

    public function filterTaxonomies($args, $output = 'names', $operator = 'and', $taxonomies = false)
    {
        $field = ('names' == $output) ? 'name' : false;

        $taxonomiesSource = (is_array($taxonomies)) ? $taxonomies : $this->getTaxonomies();

        return filterObjectList($taxonomiesSource, $args, $operator, $field);
    }

    public function setObjectTerms($objectId, $terms, $taxonomy, $append = false)
    {
        $objectId = (int)$objectId;

        if ( ! $this->taxonomyExists($taxonomy)) {
            return new EtherError('Invalid Taxonomy');
        }

        if ( ! is_array($terms)) {
            $terms = (array)$terms;
        }

        // We check for terms with zero value and unset it (Backend will send zero value term when no taxonomy terms are selected)
        if (($key = array_search(0, $terms)) !== false) {
            unset($terms[ $key ]);
        }

        $currentTerms = array_pluck($this->getObjectTerms($objectId, $taxonomy), 'id');

        sort($terms);
        sort($currentTerms);


        if ($terms == $currentTerms) {
            return [];
        }

        if ( ! $append) {
            $oldTtIds = [];

            $oldTermTaxonomies = $this->objectRepository->getTermTaxonomies($objectId, $taxonomy);
            if ($oldTermTaxonomies) {
                $oldTtIds = array_pluck($oldTermTaxonomies, 'id');
            }
        }

        $ttIds = [];
        $newTtIds = [];

        foreach ($terms as $term) {

            if ( ! strlen(trim($term))) {
                continue;
            }

            if ( ! $termInfo = $this->termExists($term, $taxonomy)) {
                if (is_int($term)) {
                    continue;
                }

                $termInfo = $this->createTerm($term, $taxonomy);
            }


            if ($termInfo instanceof EtherError) {
                return $termInfo;
            }

            $ttId = $termInfo[ 'id' ];
            $ttIds[] = $ttId;

            if ($this->termTaxonomyRelationShipsRepository->findWhereFirst([
                'object_id'        => (int)$objectId,
                'term_taxonomy_id' => (int)$ttId,
            ], ['term_taxonomy_id'])
            ) {
                continue;
            }

            $this->termTaxonomyRelationShipsRepository->create([
                'object_id'        => $objectId,
                'term_taxonomy_id' => $ttId,
            ]);
            $newTtIds[] = $ttId;
        }
        if ($newTtIds) {
            $this->updateTermCount($newTtIds);
        }

        if ( ! $append) {


            if ( ! empty($oldTtIds)) {
                $toDeleteTtIds = array_diff($oldTtIds, $ttIds);
                if ( ! empty($toDeleteTtIds)) {
                    $this->objectRepository->detachTermTaxonomies($objectId, $toDeleteTtIds);
                }
                $this->updateTermCount($toDeleteTtIds);
            }

        }


        return $ttIds;
    }

    public function getObjectTerms($objectId, $taxonomy, $args = [])
    {
        $terms = [];

        $cacheKey = 'object_terms_' . md5(implode('_', func_get_args()));

        if (Cache::tags(['taxonomies', 'get_object_terms'])->has($cacheKey)) {
            $terms = Cache::tags(['taxonomies', 'get_object_terms'])->get($cacheKey);
        } else if ($termsFound = $this->objectRepository->getPostTerms($objectId, $taxonomy)) {
            Cache::tags(['taxonomies', 'get_object_terms'])->forever($cacheKey, $termsFound);

            $terms = $termsFound;
        }

        return $terms;
    }

    public function termExists($term, $taxonomy = '', $parent = null)
    {
        return $this->getTerm($term, $taxonomy, $parent);
    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param array      $args
     *
     * @return false|Collection|\Polyether\Support\EtherError
     */
    public function createTerm($term, $taxonomy, $args = [])
    {
        $defaults = ['description' => '', 'parent' => null, 'slug' => '',];

        $args = array_merge($defaults, $args);


        if ( ! $this->taxonomyExists($taxonomy)) {
            return new EtherError('Invalid Taxonomy');
        }

        if (is_int($args[ 'parent' ]) && 0 != $args[ 'parent' ]) {

            if ( ! $this->isTaxonomyHierarchical($taxonomy)) {
                return new EtherError('Taxonomy are not hierarchical, can\'t accept parent');
            }

            if ( ! $parent = $this->termExists($args[ 'parent' ], $taxonomy)) {
                return new EtherError('Parent term id not found or not using the same taxonomy provided');
            }
        }


        if (is_int($term)) {
            if ($term == 0) {
                return new EtherError('Invalid term id');
            }

            // we check if term id exists if so we can assign it to the taxonomy
            if ( ! $this->termExists($term)) {
                return new EtherError('Term id not found');
            }

            if ( ! $this->termNotAssignedToAnyTaxonomy($term)) {
                return new EtherError('Term id already assigned to taxonomy');
            } else {
                $result = $this->termTaxonomyRepository->create([
                    'term_id'     => (int)$term,
                    'taxonomy'    => $taxonomy,
                    'description' => $args[ 'description' ],
                    'parent'      => $args[ 'parent' ],
                ]);
                if ($result) {
                    Cache::tags('taxonomies')->flush();

                    return $result;
                } else {
                    return new EtherError('Cannot create termTaxonomy');
                }
            }

        }


        if (is_string($term)) {
            if ($term == '') {
                return new EtherError('Invalid term name');
            }


            $termWithTaxonomyFound = $this->termExists($term, $taxonomy);
            $termOnlyFound = $this->termExists($term);

            if ( ! $termWithTaxonomyFound && $termOnlyFound && $this->termNotAssignedToAnyTaxonomy((int)$termOnlyFound[ 'term_id' ])) {
                return $this->createTerm((int)$termOnlyFound[ 'term_id' ], $taxonomy, $args);
            } else {
                $slug = ! empty($args[ 'slug' ]) ? $this->termRepository->createSlug($args[ 'slug' ]) : $this->termRepository->createSlug($term);

                if ($termWithTaxonomyFound && isset($parent)) {
                    $slug = $this->termRepository->sluggableTerm($slug . '-' . $parent[ 'slug' ], $taxonomy);
                } else {
                    $slug = $this->termRepository->sluggableTerm($slug, $taxonomy);
                }
                $createdTerm = $this->termRepository->create(['name' => $term, 'slug' => $slug,]);
                if ($createdTerm) {

                    $result = $this->termTaxonomyRepository->create([
                        'term_id'     => (int)$createdTerm->id,
                        'taxonomy'    => $taxonomy,
                        'description' => $args[ 'description' ],
                        'parent'      => $args[ 'parent' ],
                    ]);
                    $result->term = $createdTerm;

                    if ($result) {
                        Cache::tags('taxonomies')->flush();

                        return $result;
                    }

                    return false;
                } else {
                    return false;
                }
            }
        }
    }

    public function updateTermCount($termTaxonomyIds)
    {

        if (empty($termTaxonomyIds)) {
            return false;
        }


        if ( ! is_array($termTaxonomyIds)) {
            $termTaxonomyIds = (array)$termTaxonomyIds;
        }

        $termTaxonomyIds = array_map('intval', $termTaxonomyIds);


        foreach ($termTaxonomyIds as $termTaxonomyId) {
            $count = (int)$this->termTaxonomyRepository->countObjects($termTaxonomyId, ['post_status' => 'publish']);
            $this->termTaxonomyRepository->update(['count' => $count], $termTaxonomyId);
        }

        Cache::tags(['get_object_terms', 'get_hierarchical_terms', 'get_terms_by_taxonomy', 'taxonomy_ui_terms'])
             ->flush();
    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param null|int   $parent
     *
     * @return false|array
     */
    public function getTerm($term, $taxonomy = '', $parent = null)
    {
        $cacheKey = 'term_' . md5(implode('_', func_get_args()));

        if (Cache::tags('taxonomies')->has($cacheKey)) {
            return Cache::tags('taxonomies')->get($cacheKey);
        }

        if (is_int($term)) {
            if ($term == 0) {
                return false;
            }
            $query = $this->termRepository->getTermById($term, $taxonomy, $parent);
        }

        if (is_string($term)) {
            if ($term == '') {
                return false;
            }
            if ( ! empty($taxonomy)) {
                if (is_int($parent)) {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy($term, $taxonomy, $parent);
                } else {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy($term, $taxonomy);
                }
            } else {
                $query = $this->termRepository->getByNameOrSlug($term);
            }
        }

        if ($query) {
            Cache::tags('taxonomies')->forever($cacheKey, $query);

            return $query;
        }

        return false;

    }

    public function termNotAssignedToAnyTaxonomy($term_id)
    {
        $query = $this->termTaxonomyRepository->findWhereFirst(['term_id' => $term_id], ['id'], true);

        return (empty($query)) ? true : false;
    }

    public function updateTerm($termId, $taxonomy, $args = [])
    {

        if ( ! $this->taxonomyExists($taxonomy)) {
            return new EtherError('Invalid taxonomy');
        }

        $termId = (int)$termId;

        $term = $this->getTerm($termId, $taxonomy);

        if ( ! $term) {
            return new EtherError('Invalid Term');
        }

        $term = degrade($term);

        $args = array_merge($term, $args);

        $defaults = ['description' => '', 'parent' => null, 'slug' => '',];

        $args = array_merge($defaults, $args);

        $name = e($args[ 'name' ]);
        $description = e($args[ 'description' ]);

        $args[ 'name' ] = $name;
        $args[ 'description' ] = $description;

        if ('' == trim($name)) {
            return new EtherError('Term name is required');
        }

        if (0 == $args[ 'parent' ]) {
            $args[ 'parent' ] = null;
        }

        if ((null != $args[ 'parent' ]) && ! $this->termExists((int)$args[ 'parent' ], $taxonomy)) {
            return new EtherError('Term parent not exists');
        }


        if ($args[ 'parent' ] == $term[ 'term_id' ]) {
            return new EtherError('Term parent cannot be the same term');
        }

        if ( ! empty(trim($args[ 'slug' ]))) {
            if ($args[ 'slug' ] == $term[ 'slug' ]) {
                unset($args[ 'slug' ]);
            } else {
                $args[ 'slug' ] = $this->termRepository->sluggableTerm($args[ 'slug' ], $taxonomy);
            }
        } else {
            unset($args[ 'slug' ]);
        }

        if (isset($args[ 'id' ])) {
            unset($args[ 'id' ]);
        }
        if (isset($args[ 'term_id' ])) {
            unset($args[ 'term_id' ]);
        }

        $termUpdated = $this->termTaxonomyRepository->updateTermTaxonomy($args, $term[ 'id' ]);

        if ($termUpdated) {
            return $termUpdated;
        }

        Cache::tags(['get_object_terms', 'get_hierarchical_terms', 'get_terms_by_taxonomy', 'taxonomy_ui_terms'])
             ->flush();
    }

    public function deleteTerm($termId, $taxonomy)
    {
        if ( ! $this->taxonomyExists($taxonomy)) {
            return new EtherError('Invalid taxonomy');
        }

        $taxonomyObject = $this->getTaxonomy($taxonomy);

        if ($taxonomyObject->hierarchical) {
            $termTaxonomy = $this->termTaxonomyRepository->where('term_id', '=', $termId)
                                                         ->where('taxonomy', '=', $taxonomy)->first();
            if ( ! empty($termTaxonomy)) {
                $parent = $termTaxonomy->parent;
                $termChildren = $this->termTaxonomyRepository->findWhere(['parent' => $termTaxonomy->term_id]);

                if ( ! empty($termChildren)) {
                    $childTtIds = array_pluck($termChildren, 'id');
                    $this->termTaxonomyRepository->updateParents($childTtIds, $parent);
                }

                return $this->termTaxonomyRepository->delete($termTaxonomy->id);

            } else {
                return 0;
            }

        } else {
            $termTaxonomy = $this->termTaxonomyRepository->where('term_id', '=', $termId)
                                                         ->where('taxonomy', '=', $taxonomy)->first();
            if ( ! empty($termTaxonomy)) {
                return $this->termTaxonomyRepository->delete($termTaxonomy->id);
            } else {
                return 0;
            }
        }

    }

    public function removeObjectTerms($objectId, $termIds, $taxonomy = null)
    {
        Cache::tags('taxonomies')->flush();

        return $this->objectRepository->removePostTerms($objectId, $termIds, $taxonomy);
    }


}
