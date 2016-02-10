<?php

namespace Polyether\Taxonomy\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class TermTaxonomyRepository extends Repository
{

    public function model ()
    {
        return \Polyether\Taxonomy\Models\TermTaxonomy::class;
    }

}
