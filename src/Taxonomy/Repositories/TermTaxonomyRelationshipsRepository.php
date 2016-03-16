<?php

namespace Polyether\Taxonomy\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class TermTaxonomyRelationshipsRepository extends Repository
{

    public function model()
    {
        return \Polyether\Taxonomy\Models\TermTaxonomyRelationships::class;
    }

}
