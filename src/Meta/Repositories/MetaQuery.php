<?php

namespace Polyether\Meta\Repositories;


class MetaQuery
{
    /**
     * @param array   $queries
     * @param Builder $model
     *
     * @return Builder;
     */
    public function whereHasUserMeta($queries, $model)
    {
        $queries = $this->sanitizeQuery($queries);

        return $model->whereHas('author.userMeta', function ($q) use ($queries) {
            $this->metaQueryClause($queries, $q);
        });
    }

    /**
     * @param array $queries
     *
     * @return array
     */
    public function sanitizeQuery($queries)
    {
        $cleanQueries = array();

        if ( ! is_array($queries)) {
            return $cleanQueries;
        }

        foreach ($queries as $key => $query) {
            if ('relation' === $key) {
                $relation = $query;

            } elseif ( ! is_array($query)) {
                continue;

                // First-order clause.
            } elseif ($this->isFirstOrderClause($query)) {
                if (isset($query[ 'value' ]) && array() === $query[ 'value' ]) {
                    unset($query[ 'value' ]);
                }

                $cleanQueries[ $key ] = $query;

                // Otherwise, it's a nested query, so we recurse.
            } else {
                $cleanedQuery = $this->sanitizeQuery($query);

                if ( ! empty($cleanedQuery)) {
                    $cleanQueries[ $key ] = $cleanedQuery;
                }
            }
        }

        if (empty($cleanQueries)) {
            return $cleanQueries;
        }

        // Sanitize the 'relation' key provided in the query.
        if (isset($relation) && 'OR' === strtoupper($relation)) {
            $cleanQueries[ 'relation' ] = 'OR';

            /*
             * If there is only a single clause, call the relation 'OR'.
             * This value will not actually be used to join clauses, but it
             * simplifies the logic around combining key-only queries.
             */
        } elseif (1 === count($cleanQueries)) {
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
     * @type string    $key      Meta key to filter by.
     * @type string    $value    Meta value to filter by.
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
    public function metaQueryClause($queries, &$q, $depth = 0)
    {
        $i = 0;
        foreach ($queries as $key => $query) {
            if ('relation' === $key) {
                //if relation were found we shall skip to the next iteration
                continue;
            } else if (isset($query[ 'key' ])) {
                // That happens when a non-nested query found on the first iteration
                // in that case we the clause should be AND
                $relation = (0 === $i) ? 'AND' : $queries[ 'relation' ];
                $q->where(function ($q) use ($query) {

                    if (isset($query[ 'key' ])) {
                        $q->where('meta_key', '=', $query[ 'key' ]);
                    }

                    if (isset($query[ 'value' ])) {
                        if (isset($query[ 'compare' ])) {
                            switch (strtolower($query[ 'compare' ])) {
                                case 'between':
                                    $q->whereBetween('meta_value', $query[ 'value' ]);
                                    break;
                                case 'not between':
                                    $q->whereNotBetween('meta_value', $query[ 'value' ]);
                                    break;
                                case 'in':
                                    $q->whereIn('meta_value', $query[ 'value' ]);
                                    break;
                                case 'not in':
                                    $q->whereNotIn('meta_value', $query[ 'value' ]);
                                    break;
                                case 'is null':
                                    $q->whereIsNull('meta_value');
                                    break;
                                case 'is not null':
                                    $q->whereIsNotNull('meta_value');
                                    break;
                                default:
                                    $q->where('meta_value', $query[ 'compare' ], $query[ 'value' ]);
                            }
                        } else {
                            $q->where('meta_value', '=', $query[ 'value' ]);
                        }
                    }

                }, null, null, $relation);

            } else if (is_array($query)) {

                $depth = $depth + 1;
                // If we're going recursive for the first iterate we shall make the relation AND by default
                // else we should use the provided relation
                $relation = (1 === $depth && 0 === $i) ? 'AND' : $queries[ 'relation' ];

                $q->where(function ($q) use ($query, $depth) {
                    $this->metaQueryClause($query, $q, $depth);
                }, null, null, $relation);
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
    protected function isFirstOrderClause($query)
    {
        return isset($query[ 'key' ]) || isset($query[ 'value' ]);
    }

    /**
     * @param array   $queries
     * @param Builder $model
     *
     * @return Builder;
     */
    public function whereHasPostMeta($queries, $model)
    {
        $queries = $this->sanitizeQuery($queries);

        return $model->whereHas('postMeta', function ($q) use ($queries) {
            $this->metaQueryClause($queries, $q);
        });
    }
}