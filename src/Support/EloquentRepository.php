<?php
namespace Polyether\Support;


use Bosnadev\Repositories\Eloquent\Repository;
use Datatables;

abstract class EloquentRepository extends Repository
{
    public function sluggable( $sluggable, $slug_col )
    {
        $slug = $this->createSlug( $sluggable );

        $count = $this->model->whereRaw( "{$slug_col} RLIKE '^{$sluggable}(-[0-9]+)?$'" )->count();

        return $count ? "{$sluggable}-{$count}" : $slug;
    }

    public function createSlug( $sluggable )
    {
        return app( 'Illuminate\Support\Str' )->slug( $sluggable );
    }

    public function findWhereFirst( $where, $columns = [ '*' ], $or = false )
    {
        parent::applyCriteria();

        $model = $this->model;

        foreach ( $where as $field => $value ) {
            if ( $value instanceof \Closure ) {
                $model = ( ! $or ) ? $model->where( $value ) : $model->orWhere( $value );
            } elseif ( is_array( $value ) ) {
                if ( count( $value ) === 3 ) {
                    list( $field, $operator, $search ) = $value;
                    $model = ( ! $or ) ? $model->where( $field, $operator, $search ) : $model->orWhere( $field, $operator, $search );
                } elseif ( count( $value ) === 2 ) {
                    list( $field, $search ) = $value;
                    $model = ( ! $or ) ? $model->where( $field, '=', $search ) : $model->orWhere( $field, '=', $search );
                }
            } else {
                $model = ( ! $or ) ? $model->where( $field, '=', $value ) : $model->orWhere( $field, '=', $value );
            }
        }

        return $model->get( $columns )->first();
    }

    public function findOrFail( $id, $columns = [ '*' ] )
    {
        return $this->model->findOrFail( $id, $columns );
    }

    public function where( $field, $operator, $value )
    {
        return $this->model->where( $field, $operator, $value );
    }

    public function countWhere( $where, $or = false )
    {
        $this->applyCriteria();

        $model = $this->model;

        foreach ( $where as $field => $value ) {
            if ( $value instanceof \Closure ) {
                $model = ( ! $or ) ? $model->where( $value ) : $model->orWhere( $value );
            } elseif ( is_array( $value ) ) {
                if ( count( $value ) === 3 ) {
                    list( $field, $operator, $search ) = $value;
                    $model = ( ! $or ) ? $model->where( $field, $operator, $search ) : $model->orWhere( $field, $operator, $search );
                } elseif ( count( $value ) === 2 ) {
                    list( $field, $search ) = $value;
                    $model = ( ! $or ) ? $model->where( $field, '=', $search ) : $model->orWhere( $field, '=', $search );
                }
            } else {
                $model = ( ! $or ) ? $model->where( $field, '=', $value ) : $model->orWhere( $field, '=', $value );
            }
        }

        return $model->count();
    }

    public function findWith( $id, array $with, $columns = [ '*' ] )
    {
        $model = $this->model->find( $id, $columns );
        $model = $this->parseQuery( [ 'with' => $with ], $model );

        return $model->first();
    }

    public function parseQuery( $args, $model = null )
    {

        if ( ! $model ) {
            $model = $this->model;
        }

        if ( isset( $args[ 'has' ] ) && is_array( $args[ 'has' ] ) ) {
            $has = $args[ 'has' ];

            foreach ( $has as $key => $_has ) {
                if ( is_string( $_has ) ) {
                    $model = $model->has( $_has );
                } elseif ( is_array( $_has ) && ! empty( $_has ) ) {
                    if ( isset( $_has[ 'operator' ], $_has[ 'count' ] ) ) {
                        $model = $model->has( (string)$key, $_has[ 'operator' ], $_has[ 'count' ] );
                    }
                } else {
                    continue;
                }
            }
        }

        if ( isset( $args[ 'whereHas' ] ) && is_array( $args[ 'whereHas' ] ) ) {

            $whereHas = $this->sanitizeQuery( $args[ 'whereHas' ] );

            foreach ( $whereHas as $key => $_whereHas ) {

                if ( is_array( $_whereHas ) && ! empty( $_whereHas ) ) {
                    if ( is_string( $key ) ) {
                        $model = $model->whereHas( (string)$key, function( $q ) use ( $_whereHas ) {
                            $this->queryClause( $_whereHas, $q );
                        } );
                    }
                } else {
                    continue;
                }
            }
        }

        if ( isset( $args[ 'orWhereHas' ] ) && is_array( $args[ 'orWhereHas' ] ) ) {

            $orWhereHas = $this->sanitizeQuery( $args[ 'orWhereHas' ] );

            foreach ( $orWhereHas as $key => $_orWhereHas ) {

                if ( is_array( $_orWhereHas ) && ! empty( $_orWhereHas ) ) {
                    if ( is_string( $key ) ) {
                        $model = $model->orWhereHas( $key, function( $q ) use ( $_orWhereHas ) {
                            $this->queryClause( $_orWhereHas, $q );
                        } );
                    }
                } else {
                    continue;
                }
            }
        }

        if ( isset( $args[ 'with' ] ) && is_array( $args[ 'with' ] ) ) {
            $with = $args[ 'with' ];

            foreach ( $with as $key => $_with ) {
                if ( is_string( $_with ) ) {
                    $model = $model->with( $_with );
                } elseif ( is_array( $_with ) && ! empty( $_with ) ) {
                    $model = $model->with( [ (string)$key => function( $q ) use ( $_with, $model ) {
                        $this->parseQuery( $_with, $q );
                    } ] );
                } else {
                    continue;
                }
            }
        }

        if ( isset( $args[ 'columns' ] ) ) {
            if ( ! is_array( $args[ 'columns' ] ) ) {
                $args[ 'columns' ] = (array)$args[ 'columns' ];
            }

            $columns = $args[ 'columns' ];
            $model = $model->select( $columns );
        }

        if ( isset( $args[ 'query' ] ) ) {
            $queries = $this->sanitizeQuery( $args[ 'query' ] );
            $model = $model->where( function( $q ) use ( $queries ) {
                $this->queryClause( $queries, $q );
            } );
        }

        return $model;
    }

    /**
     * @param array $queries
     *
     * @return array
     */
    public function sanitizeQuery( $queries )
    {
        $cleanQueries = array();

        if ( ! is_array( $queries ) ) {
            return $cleanQueries;
        }

        foreach ( $queries as $key => $query ) {
            if ( 'relation' === $key ) {
                $relation = $query;

            } elseif ( ! is_array( $query ) ) {
                continue;

                // First-order clause.
            } elseif ( $this->isFirstOrderClause( $query ) ) {
                if ( isset( $query[ 'value' ] ) && array() === $query[ 'value' ] ) {
                    unset( $query[ 'value' ] );
                }

                $cleanQueries[ $key ] = $query;

                // Otherwise, it's a nested query, so we recurse.
            } else {
                $cleanedQuery = $this->sanitizeQuery( $query );

                if ( ! empty( $cleanedQuery ) ) {
                    $cleanQueries[ $key ] = $cleanedQuery;
                }
            }
        }

        if ( empty( $cleanQueries ) ) {
            return $cleanQueries;
        }

        // Sanitize the 'relation' key provided in the query.
        if ( isset( $relation ) && 'OR' === strtoupper( $relation ) ) {
            $cleanQueries[ 'relation' ] = 'OR';

            // Default to AND.
        } else {
            $cleanQueries[ 'relation' ] = 'AND';
        }

        return $cleanQueries;
    }

    /**
     * @param  array   $queries  {
     *
     * @type string    $relation Optional. The MySQL keyword used to join the clauses of the query. Accepts 'AND', Or
     *       'OR'. Default 'AND'.
     *
     * @type array {
     * @type string    $column   column to filter by.
     * @type string    $value    Column value to filter by.
     * @type string    $compare  MySQL operator used for comparing the $value. Accepts '=',
     *                               '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
     *                               'BETWEEN', 'NOT BETWEEN', 'REGEXP', 'NOT REGEXP', or 'RLIKE'.
     *                               Default is '='
     *             }
     * }
     *
     * @param  Builder $q
     * @param  int     $depth
     */
    public function queryClause( $queries, &$q, $depth = 0 )
    {
        $i = 0;
        foreach ( $queries as $key => $query ) {
            if ( 'relation' === $key ) {
                //if relation were found we shall skip to the next iteration
                continue;
            } else if ( isset( $query[ 'column' ] ) ) {
                // That happens when a non-nested query found on the first iteration
                // in that case we the clause should be AND
                $relation = ( 0 === $i ) ? 'AND' : $queries[ 'relation' ];
                $q->where( function( $q ) use ( $query ) {

                    if ( isset( $query[ 'value' ] ) ) {
                        if ( isset( $query[ 'compare' ] ) ) {
                            switch ( strtolower( $query[ 'compare' ] ) ) {
                                case 'between':
                                    $q->whereBetween( $query[ 'column' ], $query[ 'value' ] );
                                    break;
                                case 'not between':
                                    $q->whereNotBetween( $query[ 'column' ], $query[ 'value' ] );
                                    break;
                                case 'in':
                                    $q->whereIn( $query[ 'column' ], $query[ 'value' ] );
                                    break;
                                case 'not in':
                                    $q->whereNotIn( $query[ 'column' ], $query[ 'value' ] );
                                    break;
                                case 'is null':
                                    $q->whereIsNull( $query[ 'column' ] );
                                    break;
                                case 'is not null':
                                    $q->whereIsNotNull( $query[ 'column' ] );
                                    break;
                                default:
                                    $q->where( $query[ 'column' ], $query[ 'compare' ], $query[ 'value' ] );
                            }
                        } else {
                            $q->where( $query[ 'column' ], '=', $query[ 'value' ] );
                        }
                    }

                }, null, null, $relation );

            } else if ( is_array( $query ) ) {

                $depth = $depth + 1;
                // If we're going recursive for the first iterate we shall make the relation AND by default
                // else we should use the provided relation
                $relation = ( 1 === $depth && 0 === $i ) ? 'AND' : $queries[ 'relation' ];

                $q->where( function( $q ) use ( $query, $depth ) {
                    $this->queryClause( $query, $q, $depth );
                }, null, null, $relation );
            } else {
                continue;
            }
            $i++;
        }
    }

    /**
     * @param array $query
     *
     * @return bool
     */
    protected function isFirstOrderClause( $query )
    {
        return isset( $query[ 'column' ] ) && isset( $query[ 'value' ] );
    }


    public function paginate( $perPage = 20, $columns = array( '*' ) )
    {
        return parent::paginate( $perPage, $columns );
    }

    /**
     * @param $args
     *
     * @return mixed
     */
    public function dataTable( $args, $model = null )
    {
        $model = $this->parseQuery( $args, $model );
        $dataTables = Datatables::of( $model );

        return $dataTables;
    }
}