<?php

namespace Polyether\Taxonomy\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class TermRepository extends Repository
{

    public function model()
    {
        return \Polyether\Taxonomy\Models\Term::class;
    }

    public function sluggableTerm( $sluggable, $taxonomy )
    {
        $slug = $this->createSlug( $sluggable );
        $count = $this->model->join( 'term_taxonomy', 'terms.id', '=', 'term_taxonomy.term_id' )
                             ->whereRaw( "term_taxonomy.taxonomy = '{$taxonomy}' && terms.slug RLIKE '^{$slug}(-[0-9]+)?$'" )
                             ->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function getTermById( $term_id, $taxonomy = '', $parent = null )
    {


        if ( ! empty( $taxonomy ) ) {
            $params[ 'term_id' ] = $term_id;
            $params[ 'taxonomy' ] = $taxonomy;
            if ( is_int( $parent ) ) {
                $params[ 'parent' ] = $parent;
            }

            $query = $this->model->find( $term_id );

            if ( null != $query ) {
                $result = $query->join( 'term_taxonomy', 'terms.id', '=', 'term_taxonomy.term_id' )->where( $params )
                                ->first();
            } else {
                return false;
            }

        } else {
            $params[ 'id' ] = $term_id;
            $result = $this->model->select( 'id as term_id' )->find( $term_id );
        }

        return ( null != $result ) ? $result : false;
    }

    public function getTermTaxonomy( $taxonomy, $termId, $parent = null )
    {
        $result = $this->model->join( 'term_taxonomy', function( $join ) {
            $join->on( 'terms.id', '=', 'term_taxonomy.term_id' );
        } )->where( function( $where ) use ( $parent, $taxonomy ) {
            $where->where( 'taxonomy', '=', $taxonomy );
            if ( is_int( $parent ) ) {
                $where->where( 'term_taxonomy.parent', '=', (int)$parent );
            }
        } )->where( 'terms.id', '=', $termId )->first();

        return null != ( $result ) ? $result : false;
    }

    public function getByNameOrSlug( $term )
    {
        $result = $this->model->select( 'id as term_id' )->where( 'name', '=', $term )
                              ->orWhere( 'slug', '=', $this->createSlug( $term ) )->first();
        if ( null !== $result ) {
            return $result;
        }

        return false;
    }

    public function getByNameOrSlugWithTaxonomy( $term, $taxonomy, $parent = null )
    {
        $result = $this->model->join( 'term_taxonomy', function( $join ) {
            $join->on( 'terms.id', '=', 'term_taxonomy.term_id' );
        } )->where( function( $where ) use ( $term, $taxonomy, $parent ) {
            $where->where( 'name', '=', $term )->orWhere( 'slug', '=', $this->createSlug( $term ) );
        } )->where( function( $where ) use ( $parent, $taxonomy ) {
            $where->where( 'taxonomy', '=', $taxonomy );
            if ( is_numeric( $parent ) ) {
                $where->where( 'term_taxonomy.parent', '=', (int)$parent );
            }
        } )->first();

        return null != ( $result ) ? $result : false;
    }

}
