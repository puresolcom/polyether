<?php

namespace Polyether\Taxonomy\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class TermRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Taxonomy\Models\Term::class;
    }

    public function getTermById ($term_id, $taxonomy = '', $parent = null)
    {


        if ( ! empty($taxonomy)) {
            $params[ 'term_id' ] = $term_id;
            $params[ 'taxonomy' ] = $taxonomy;
            if (is_int($parent))
                $params[ 'parent' ] = $parent;

            $query = $this->model->find($term_id);

            if (null != $query) {
                $result = $query->taxonomy()->where($params)->first();
            } else {
                return false;
            }

        } else {
            $params[ 'id' ] = $term_id;
            $result = $this->model->select('id as term_id')->find($term_id);
        }

        return (null != $result) ? $result->toArray() : false;
    }

    public function getByNameOrSlug ($term)
    {
        $result = $this->model->select('id as term_id')->where('name', '=', $term)->orWhere('slug', '=', $this->createSlug($term))->first();
        if (null !== $result)
            return $result->toArray();

        return false;
    }

    public function getByNameOrSlugWithTaxonomy ($term, $taxonomy, $parent = null)
    {
        $result = $this->model->leftJoin('term_taxonomy', function ($join) {
            $join->on('terms.id', '=', 'term_taxonomy.term_id');
        })->where(function ($where) use ($term, $taxonomy, $parent) {
            $where->where('name', '=', $term)->orWhere('slug', '=', $this->createSlug($term));
        })->where(function ($where) use ($parent, $taxonomy) {
            $where->where('taxonomy', '=', $taxonomy);
            if (is_numeric($parent))
                $where->where('term_taxonomy.parent', '=', (int)$parent);
        })->first();

        return null != ($result) ? $result->toArray() : false;
    }

}
