<?php

namespace Polyether\Taxonomy\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class TermTaxonomyRelationshipsRepository extends Repository {

    public function model() {
        return \Polyether\Taxonomy\Models\TermTaxonomyRelationships::class;
    }

}
