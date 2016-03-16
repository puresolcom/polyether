<?php

namespace Polyether\Taxonomy\Repositories;

use Datatables;
use DB;
use Illuminate\Support\Collection;
use Polyether\Support\EloquentRepository as Repository;

class TermTaxonomyRepository extends Repository
{

    public function model()
    {
        return \Polyether\Taxonomy\Models\TermTaxonomy::class;
    }

    public function getTermByNameOrSlug( $term )
    {
        return $this->model->term()->where( 'name', '=', $term )->orWhere( 'slug', '=', $term )->first();
    }

    public function getTaxonomyTerms( $taxonomy, array $args )
    {
        $orderBy = ( isset( $args[ 'orderby' ] ) ) ? $args[ 'orderby' ] : 'id';
        $order = ( isset( $args[ 'order' ] ) ) ? $args[ 'order' ] : 'ASC';
        $limit = ( isset( $args[ 'limit' ] ) ) ? (int)$args[ 'limit' ] : null;

        $model = $this->model->where( function( $query ) use ( $taxonomy, $args ) {
            $query->where( 'taxonomy', '=', $taxonomy );

            if ( $args[ 'hide_empty' ] ) {
                $query->where( 'count', '>', 0 );
            }

            if ( ! empty( $args[ 'exclude' ] ) ) {
                $query->whereNotIn( 'term_id', $args[ 'exclude' ] );
            }
        } )->with( [ 'term' => function( $query ) {
            $query->orderBy( 'id', 'ASC' );
        } ] );


        if ( isset( $limit ) ) {
            $model = $model->limit( $limit );
        }

        $model = $model->get()->pluck( 'term' );

        return ( null != ( $model ) ) ? $model : null;
    }

    public function updateTermTaxonomy( array $data, $id )
    {
        if ( isset( $data[ 'id' ] ) ) {
            unset( $data[ 'id' ] );
        }
        if ( isset( $data[ 'term_id' ] ) ) {
            unset( $data[ 'term_id' ] );
        }

        $model = $this->model->join( 'terms', 'term_taxonomy.term_id', '=', 'terms.id' )
                             ->where( 'term_taxonomy.id', '=', $id );
        if ( $model ) {
            return $model->update( $data );
        }

        return false;
    }

    public function updateParents( $ids, $parentId )
    {
        return $this->model->whereIn( 'id', $ids )->update( [ 'parent' => $parentId ] );
    }

    public function ajaxGetSelect2TaxonomyTerms( $taxonomy, $like, $value = 'term_id', $limit = 10, $offset = 0 )
    {
        if ( $value == 'slug' ) {
            $selectedValue = ', terms.slug AS id';
        } else {
            $selectedValue = ', term_taxonomy.' . $value . ' AS id';
        }

        return DB::table( 'term_taxonomy' )->selectRaw( 'terms.name AS text ' . e( $selectedValue ) )
                 ->where( 'taxonomy', '=', $taxonomy )->join( 'terms', 'term_taxonomy.term_id', '=', 'terms.id' )
                 ->where( 'terms.name', 'LIKE', "%{$like}%" )->orderBy( 'terms.name' )->skip( $offset )->take( $limit )
                 ->get();
    }

    public function getTaxonomyWithTerms( $taxonomy, array $args )
    {

        $orderBy = ( isset( $args[ 'orderby' ] ) ) ? $args[ 'orderby' ] : 'id';
        $order = ( isset( $args[ 'order' ] ) ) ? $args[ 'order' ] : 'ASC';


        $model = $this->model->where( function( $query ) use ( $taxonomy, $args ) {
            $query->where( 'taxonomy', '=', $taxonomy );

            if ( $args[ 'hide_empty' ] ) {
                $query->where( 'count', '>', 0 );
            }

            if ( ! empty( $args[ 'exclude' ] ) ) {
                $query->whereNotIn( 'term_id', $args[ 'exclude' ] );
            }
        } )->with( 'term' )->orderBy( 'id', 'ASC' );

        $limit = ( isset( $args[ 'limit' ] ) ) ? (int)$args[ 'limit' ] : null;

        if ( isset( $limit ) ) {
            $model = $model->limit( $limit );
        }

        $model = $model->get();

        return ( null != ( $model ) ) ? $model : null;
    }

    public function countObjects( $termTaxonomyId, $where = [ ] )
    {
        if ( ! $model = $this->model->find( $termTaxonomyId ) ) {
            return false;
        }

        return $model->posts()->where( $where )->count();
    }

    public function dataTable( $args, $model = null )
    {
        $model = $this->model->join( 'terms', 'term_taxonomy.term_id', '=', 'terms.id' );

        $model = $this->parseQuery( $args, $model );

        $model = $model->get();

        $hierarchicalTerms = $this->_buildTermsTree( $model );

        $terms = $this->_termRecursiveName( $hierarchicalTerms );


        $terms = new Collection( $terms );


        $dataTables = Datatables::of( $terms );

        return $dataTables;
    }

    private function _buildTermsTree( $taxonomyTerms, $parent = null, $depth = 0 )
    {
        $branch = [ ];

        if ( count( $taxonomyTerms ) > 0 ) {
            foreach ( $taxonomyTerms as $taxonomyTerm ) {
                if ( $parent == $taxonomyTerm[ 'parent' ] ) {
                    $hasChildren = $this->_buildTermsTree( $taxonomyTerms, $taxonomyTerm[ 'id' ], $depth + 1 );
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

    private function _termRecursiveName( $terms, $name = '', $separator = '&rsaquo;' )
    {
        $result = [ ];
        foreach ( $terms as $term ) {
            if ( ! empty( $name ) ) {
                $term->name = $name . ' ' . $separator . ' ' . $term->name;
            }

            if ( isset( $term->children ) ) {
                $result = array_merge( $result, $this->_termRecursiveName( $term->children, $term->name ) );
            }
            $result[] = $term;
        }

        return $result;

    }

}
