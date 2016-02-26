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

    public function __construct ( TermRepository $termRepository, TermTaxonomyRepository $termTaxonomyRepository, TermTaxonomyRelationshipsRepository $termTaxonomyRelationshipsRepository, PostRepository $objectRepository )
    {
        $this->termRepository = $termRepository;
        $this->termTaxonomyRepository = $termTaxonomyRepository;
        $this->termTaxonomyRelationShipsRepository = $termTaxonomyRelationshipsRepository;
        $this->objectRepository = $objectRepository;

        $this->registerDefaultTaxonomies();
    }

    public function registerDefaultTaxonomies ()
    {
        $this->registerTaxonomy( 'category', 'post', [ 'hierarchical' => true, '_built_in' => true ] );
        $this->registerTaxonomy( 'post_tag', 'post', [ 'hierarchical' => false, '_built_in' => true ] );
        $this->registerTaxonomy( 'nav_menu', 'post', [ 'labels'       => [ 'name'     => 'Navigation Menus',
                                                                           'singular' => 'Navigation Menu' ],
                                                       'hierarchical' => true, 'show_ui' => false,
                                                       '_built_in'    => true ] );
    }

    /**
     * @param string       $taxonomy
     * @param string|array $object_type
     * @param array        $args
     *
     * @return \Polyether\Support\EtherError
     */
    public function registerTaxonomy ( $taxonomy, $object_type, $args = array() )
    {

        if ( empty( $taxonomy ) || strlen( $taxonomy ) > 32 )
            return new EtherError( 'Taxonomy names must be between 1 and 32 characters in length.' );

        if ( empty( $object_type ) ) {
            return new EtherError( 'Object type can not be empty' );
        }

        $defaults = [ 'labels'           => [ ], 'show_ui' => true, 'show_in_admin_menu' => null,
                      'show_in_nav_menu' => null, 'hierarchical' => false, '_built_in' => false ];


        $args = array_merge( $defaults, $args );


        if ( null === $args[ 'show_in_admin_menu' ] )
            $args[ 'show_in_admin_menu' ] = $args[ 'show_ui' ];

        if ( null === $args[ 'show_in_nav_menu' ] )
            $args[ 'show_in_nav_menu' ] = $args[ 'show_ui' ];

        $args[ 'object_type' ] = array_unique( (array)$object_type );

        $this->taxonomies[ $taxonomy ] = (object)$args;
    }

    public function unregisterTaxonomy ( $taxonomy )
    {
        if ( isset( $this->taxonomies[ $taxonomy ] ) ) {
            unset( $this->taxonomies[ $taxonomy ] );

            return true;
        }

        return false;
    }

    public function registerTaxonomyForObjectType ( $taxonomy, $object_type )
    {
        if ( ! isset( $this->taxonomies[ $taxonomy ] ) )
            return false;

        $new_object_type = (array)$object_type;

        $current_object_type = $this->taxonomies[ $taxonomy ]->object_type;

        $this->taxonomies[ $taxonomy ]->object_type = array_unique( array_merge( $current_object_type, $new_object_type ) );
    }

    public function getTaxonomies ()
    {
        return $this->taxonomies;
    }

    public function UITerms ( $args = [ ] )
    {
        $defaults = [ 'show_options_all' => '', 'show_option_none' => '', 'option_none_value' => '', 'orderby' => 'id',
                      'order'            => 'ASC', 'with_post_counts' => false, 'hide_empty' => false, 'exclude' => [ ],
                      'echo'             => true, 'hierarchical' => false, 'spacer' => '&nbsp;&nbsp;', 'name' => 'cat',
                      'id'               => '', 'class' => '', 'selected' => 0, 'value_field' => 'term_id',
                      'taxonomy'         => 'category', ];

        $args = array_merge( $defaults, $args );

        $echo = $args[ 'echo' ];

        $cacheKey = 'taxonomy_ui_terms_' . md5( http_build_query( $args ) );

        if ( Cache::tags( 'taxonomy_ui_terms' )->has( $cacheKey ) ) {
            $output = Cache::tags( 'taxonomy_ui_terms' )->get( $cacheKey );
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

            if ( $isHierarchical ) {
                $taxonomyTerms = $this->getHierarchicalTerms( $taxonomy, null, $args );
            } else {
                $taxonomyTerms = $this->getTermsByTaxonomy( $taxonomy, $args );
            }

            if ( $isHierarchical ) {
                $output = "<div id=\"{$id}\" name=\"{$name}\" class=\"hierarchical-taxonomy-wrapper {$class}\"><ul class='taxonomy-list'>
                          <input type=\"hidden\" name='taxonomy[{$taxonomy}][]' value='0' ";

                if ( ! empty( $taxonomyTerms ) ) {

                    $selected = ( $selected == 0 ) ? 'checked = "checked"' : '';

                    if ( $showOptionsAll ) {
                        $output .= "<li>
                                        <div class=\"checkbox\">
                                            <label>
                                            <input type=\"checkbox\" {$selected} name=\"taxonomy[{$taxonomy}][]\" value=\"{$valueField}\">
                                            {$showOptionsAll}
                                            </label>
                                        </div>
                                    </li>";
                    }

                    $output .= $this->_termsCheckboxWalker( $taxonomyTerms, $args );

                } else {
                    $output .= "<li>{$showOptionNone}</li>";
                }

                $output .= '</ul></div>';
            } else {
                $output = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\">";


                if ( ! empty( $taxonomyTerms ) ) {

                    $selected = ( $selected == 0 ) ? 'selected = "selected"' : '';

                    if ( $showOptionsAll ) {
                        $output .= "<option {$selected} value=\"0\">{$showOptionsAll}</option>";
                    }
                    $output .= $this->_termsDropdownWalker( $taxonomyTerms, $args );

                } else {
                    $output .= "<option value=\"{$optionNoneValue}\">{$showOptionNone}</option>";
                }

                $output .= '</select>';
            }


            Cache::tags( [ 'taxonomies', 'taxonomy_ui_terms' ] )->forever( $cacheKey, $output );
        }

        if ( $echo ) {
            echo $output;
        } else {
            return $output;
        }
    }

    public function getHierarchicalTerms ( $taxonomy, $parent = null, $args = [ ] )
    {
        $cacheKey = 'get_hierarchical_terms_' . $taxonomy . '_' . $parent . '_' . md5( http_build_query( $args ) );
        $result = [ ];

        if ( Cache::tags( 'get_hierarchical_terms' )->has( $cacheKey ) )
            return Cache::tags( 'get_hierarchical_terms' )->get( $cacheKey );

        if ( $this->taxonomyExists( $taxonomy ) && $this->isTaxonomyHierarchical( $taxonomy ) ) {
            $taxonomyTerms = $this->getTermsByTaxonomy( $taxonomy, $args );
            if ( count( $taxonomyTerms ) > 0 ) {
                $result = $this->_buildTermsTree( $taxonomyTerms, $parent );
                Cache::tags( [ 'taxonomies', 'get_hierarchical_terms' ] )->forever( $cacheKey, $result );
            }
        }

        return $result;
    }

    public function getTermsByTaxonomy ( $taxonomy, $args = [ ] )
    {
        $defaults = [ 'orderby' => 'id', 'order' => 'ASC', 'hide_empty' => false, 'exclude' => [ ], ];
        $args = array_merge( $defaults, $args );
        $result = [ ];
        $cacheKey = 'get_terms_by_taxonomy_' . $taxonomy . '_' . md5( http_build_query( $args ) );

        if ( Cache::tags( [ 'taxonomies', 'get_terms_by_taxonomy' ] )->has( $cacheKey ) )
            return Cache::tags( [ 'taxonomies', 'get_terms_by_taxonomy' ] )->get( $cacheKey );

        if ( $this->taxonomyExists( $taxonomy ) ) {
            $termTaxonomies = $this->termTaxonomyRepository->getTaxonomyTerms( $taxonomy, $args );

            if ( $termTaxonomies ) {
                Cache::tags( [ 'taxonomies', 'get_terms_by_taxonomy' ] )->forever( $cacheKey, $termTaxonomies );
                $result = $termTaxonomies;
            }
        }

        return $result;
    }

    private function _termsCheckboxWalker ( array $taxonomyTerms, $args )
    {
        $taxonomy = $args[ 'taxonomy' ];
        $withPostCounts = $args[ 'with_post_counts' ];
        $selectedValues = (array)$args[ 'selected' ];
        $valueField = $args[ 'value_field' ];
        $output = '';
        foreach ( $taxonomyTerms as $taxonomyTerm ) {
            $selected = ( ! empty( $selectedValues ) && in_array( $taxonomyTerm[ $valueField ], $selectedValues ) ) ? 'checked = "checked"' : '';
            $postCounts = ( $withPostCounts ) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
            $value = isset( $taxonomyTerm[ $valueField ] ) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
            $output .= "<li>
                            <div class=\"checkbox\">
                                <label>
                                <input type=\"checkbox\" {$selected} value=\"{$value}\" name=\"taxonomy[{$taxonomy}][]\">
                                {$taxonomyTerm['term']['name']}{$postCounts}
                                </label>
                            </div>
                            ";
            $hasChildren = ( isset( $taxonomyTerm[ 'children' ] ) && ! empty( $taxonomyTerm[ 'children' ] ) ) ? true : false;
            if ( ! empty( $hasChildren ) ) {
                $output .= '<ul>';
                $output .= $this->_termsCheckboxWalker( $taxonomyTerm[ 'children' ], $args );
                $output .= '</ul>';
                $output .= '</li>';
            } else {
                $output .= '</li>';
            }
        }

        return $output;
    }

    private function _termsDropdownWalker ( array $taxonomyTerms, $args )
    {
        $withPostCounts = $args[ 'with_post_counts' ];
        $selectedValue = $args[ 'selected' ];
        $valueField = $args[ 'value_field' ];
        $output = '';

        foreach ( $taxonomyTerms as $taxonomyTerm ) {
            $selected = ( $selectedValue != 0 && $selectedValue == $taxonomyTerm[ $valueField ] ) ? 'selected = "selected"' : '';
            $postCounts = ( $withPostCounts ) ? ' (' . $taxonomyTerm[ 'count' ] . ')' : '';
            $value = isset( $taxonomyTerm[ $valueField ] ) ? $taxonomyTerm[ $valueField ] : $taxonomyTerm[ 'term' ][ $valueField ];
            $output .= "<option $selected value=\"{$value}\">{$taxonomyTerm['term']['name']}{$postCounts}</option>";
            $hasChildren = ( isset( $taxonomyTerm[ 'children' ] ) && ! empty( $taxonomyTerm[ 'children' ] ) ) ? true : false;
            if ( $hasChildren ) {
                $output .= $this->_termsDropdownWalker( $taxonomyTerm[ 'children' ], $args );
            }
        }

        return $output;
    }

    public function taxonomyExists ( $taxonomy )
    {
        if ( ! is_string( $taxonomy ) )
            return false;

        return isset( $this->taxonomies[ $taxonomy ] );
    }

    public function isTaxonomyHierarchical ( $taxonomy )
    {
        if ( ! $this->taxonomyExists( $taxonomy ) )
            return false;

        $taxonomy = $this->getTaxonomy( $taxonomy );

        return $taxonomy->hierarchical;
    }

    protected function _buildTermsTree ( array $taxonomyTerms, $parent = null, $depth = 0 )
    {
        $branch = [ ];

        if ( count( $taxonomyTerms ) > 0 ) {
            foreach ( $taxonomyTerms as $taxonomyTerm ) {
                if ( $parent == $taxonomyTerm[ 'parent' ] ) {
                    $hasChildren = $this->_buildTermsTree( $taxonomyTerms, $taxonomyTerm[ 'term_id' ], $depth + 1 );
                    if ( $hasChildren ) {
                        $taxonomyTerm[ 'children' ] = $hasChildren;
                    }
                    $taxonomyTerm[ 'depth' ] = $depth;
                    $branch[] = $taxonomyTerm;
                }
            }
        }

        return $branch;
    }

    public function getTaxonomy ( $taxonomy )
    {

        if ( ! $this->taxonomyExists( $taxonomy ) )
            return false;

        return $this->taxonomies[ $taxonomy ];
    }

    public function setObjectTerms ( $objectId, $terms, $taxonomy, $append = false )
    {
        $objectId = (int)$objectId;

        if ( ! $this->taxonomyExists( $taxonomy ) )
            return new EtherError( 'Invalid Taxonomy' );

        if ( ! is_array( $terms ) )
            $terms = (array)$terms;

        // We check for terms we zero value and unset it (Backend will send zero value term when using hierarchical terms checkboxes)

        if ( ( $key = array_search( 0, $terms ) ) !== false ) {
            unset( $terms[ $key ] );
        }

        $currentTerms = array_pluck( $this->getObjectTerms( $objectId, $taxonomy ), 'id' );

        sort( $terms );
        sort( $currentTerms );

        if ( $terms == $currentTerms )
            return [ ];

        $ttIds = [ ];
        $newTtIds = [ ];

        foreach ( $terms as $term ) {

            if ( ! strlen( trim( $term ) ) )
                continue;

            if ( ! $termInfo = $this->termExists( $term, $taxonomy ) ) {
                if ( is_int( $term ) )
                    continue;

                $termInfo = $this->createTerm( $term, $taxonomy );
            }


            if ( $termInfo instanceof EtherError ) {
                return $termInfo;
            }

            $ttId = $termInfo[ 'id' ];
            $ttIds[] = $ttId;

            if ( $this->termTaxonomyRelationShipsRepository->findWhereFirst( [ 'object_id'        => (int)$objectId,
                                                                               'term_taxonomy_id' => (int)$ttId ], [ 'term_taxonomy_id' ] )
            )
                continue;

            $this->termTaxonomyRelationShipsRepository->create( [ 'object_id'        => $objectId,
                                                                  'term_taxonomy_id' => $ttId ] );
            $newTtIds[] = $ttId;
        }

        if ( ! $append ) {
            if ( $sync = $this->objectRepository->syncTermTaxonomies( $objectId, $ttIds ) ) {
                if ( ! empty( $sync[ 'detached' ] ) )
                    $this->updateTermCount( (array)$sync[ 'detached' ] );
            }

        }

        if ( $newTtIds )
            $this->updateTermCount( $newTtIds );

        return $ttIds;
    }

    public function getObjectTerms ( $objectId, $taxonomy, $args = [ ] )
    {

        $terms = [ ];

        $cacheKey = 'object_terms_' . md5( implode( '_', func_get_args() ) );

        if ( Cache::tags( [ 'taxonomies', 'get_object_terms' ] )->has( $cacheKey ) ) {
            $terms = Cache::tags( [ 'taxonomies', 'get_object_terms' ] )->get( $cacheKey );
        } else if ( $termsFound = $this->objectRepository->getPostTerms( $objectId, $taxonomy ) ) {
            Cache::tags( [ 'taxonomies', 'get_object_terms' ] )->forever( $cacheKey, $termsFound );

            $terms = $termsFound;
        }

        return $terms;
    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param null|int   $parent
     *
     * @return false|array
     */
    public function termExists ( $term, $taxonomy = '', $parent = null )
    {
        $cacheKey = 'term_' . md5( implode( '_', func_get_args() ) );

        if ( Cache::tags( 'taxonomies' )->has( $cacheKey ) ) {
            return Cache::tags( 'taxonomies' )->get( $cacheKey );
        }

        if ( is_int( $term ) ) {
            if ( $term == 0 )
                return false;
            $query = $this->termRepository->getTermById( $term, $taxonomy, $parent );
        }

        if ( is_string( $term ) ) {
            if ( $term == '' )
                return false;
            if ( ! empty( $taxonomy ) ) {
                if ( is_int( $parent ) ) {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy( $term, $taxonomy, $parent );
                } else {
                    $query = $this->termRepository->getByNameOrSlugWithTaxonomy( $term, $taxonomy );
                }
            } else {
                $query = $this->termRepository->getByNameOrSlug( $term );
            }
        }

        if ( $query ) {
            Cache::tags( 'taxonomies' )->forever( $cacheKey, $query );

            return $query;
        }

        return false;

    }

    /**
     * @param int|string $term
     * @param string     $taxonomy
     * @param array      $args
     *
     * @return false|Collection|\Polyether\Support\EtherError
     */
    public function createTerm ( $term, $taxonomy, $args = [ ] )
    {
        $defaults = [ 'description' => '', 'parent' => null, 'slug' => '', ];

        $args = array_merge( $defaults, $args );


        if ( ! $this->taxonomyExists( $taxonomy ) ) {
            return new EtherError( 'Invalid Taxonomy' );
        }

        if ( is_int( $args[ 'parent' ] ) ) {

            if ( ! $this->isTaxonomyHierarchical( $taxonomy ) )
                return new EtherError( 'Taxonomy are not hierarchical, can\'t accept parent' );

            if ( ! $this->termExists( $args[ 'parent' ], $taxonomy ) )
                return new EtherError( 'Parent term id not found or not using the same taxonomy provided' );
        }


        if ( is_int( $term ) ) {
            if ( $term == 0 )
                return new EtherError( 'Invalid term id' );

            // we check if term id exists if so we can assign it to the taxonomy
            if ( ! $this->termExists( $term ) )
                return new EtherError( 'Term id not found' );

            if ( ! $this->termNotAssignedToAnyTaxonomy( $term ) ) {
                return new EtherError( 'Term id already assigned to taxonomy' );
            } else {
                $result = $this->termTaxonomyRepository->create( [ 'term_id'     => (int)$term, 'taxonomy' => $taxonomy,
                                                                   'description' => $args[ 'description' ],
                                                                   'parent'      => $args[ 'parent' ], ] );
                if ( $result ) {
                    Cache::tags( 'taxonomies' )->flush();

                    return $result;
                } else {
                    return new EtherError( 'Cannot create termTaxonomy' );
                }
            }

        }


        if ( is_string( $term ) ) {
            if ( $term == '' )
                return new EtherError( 'Invalid term name' );

            $termWithTaxonomyFound = $this->termExists( $term, $taxonomy );

            if ( $termWithTaxonomyFound && null != $args[ 'parent' ] && $args[ 'parent' ] == $termWithTaxonomyFound[ 'parent' ] ) {
                return new EtherError( 'Term already assigned to the taxonomy' );
            } else {
                if ( ( $termOnlyFound = $this->termExists( $term ) ) && $this->termNotAssignedToAnyTaxonomy( (int)$termOnlyFound[ 'term_id' ] ) ) {
                    return $this->createTerm( (int)$termWithTaxonomyFound[ 'term_id' ], $taxonomy, $args );
                } else {

                    $slug = ! empty( $args[ 'slug' ] ) ? $this->termRepository->createSlug( $args[ 'slug' ] ) : $this->termRepository->createSlug( $term );

                    if ( $termWithTaxonomyFound ) {
                        $slug = $this->termRepository->sluggable( $slug, 'slug' );
                    }
                    $createdTerm = $this->termRepository->create( [ 'name' => $term, 'slug' => $slug, ] );
                    if ( $createdTerm ) {

                        $result = $this->termTaxonomyRepository->create( [ 'term_id'     => (int)$createdTerm->id,
                                                                           'taxonomy'    => $taxonomy,
                                                                           'description' => $args[ 'description' ],
                                                                           'parent'      => $args[ 'parent' ], ] );
                        if ( $result ) {
                            Cache::tags( 'taxonomies' )->flush();

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

    public function updateTermCount ( $termTaxonomyIds )
    {

        if ( empty( $termTaxonomyIds ) )
            return false;

        if ( ! is_array( $termTaxonomyIds ) )
            $termTaxonomyIds = (array)$termTaxonomyIds;

        $termTaxonomyIds = array_map( 'intval', $termTaxonomyIds );

        foreach ( $termTaxonomyIds as $termTaxonomyId ) {
            $count = (int)$this->termTaxonomyRepository->countObjects( $termTaxonomyId, [ 'post_status' => 'publish' ] );
            $this->termTaxonomyRepository->update( [ 'count' => $count ], $termTaxonomyId );
        }

        Cache::tags( [ 'get_object_terms', 'get_hierarchical_terms', 'get_terms_by_taxonomy',
                       'taxonomy_ui_terms' ] )->flush();
    }

    public function termNotAssignedToAnyTaxonomy ( $term_id )
    {
        $query = $this->termTaxonomyRepository->findWhereFirst( [ 'term_id' => $term_id ], [ 'id' ], true );

        return ( empty( $query ) ) ? true : false;
    }

    public function removeObjectTerms ( $objectId, $termIds, $taxonomy = null )
    {
        Cache::tags( 'taxonomies' )->flush();

        return $this->objectRepository->removePostTerms( $objectId, $termIds, $taxonomy );
    }


}
