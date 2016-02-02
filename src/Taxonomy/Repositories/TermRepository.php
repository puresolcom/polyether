<?php

namespace Polyether\Taxonomy\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class TermRepository extends Repository {

    public function model() {
        return \Polyether\Taxonomy\Models\Term::class;
    }

}
