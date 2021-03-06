<?php

namespace Polyether\Option\Repositories;

use Polyether\Support\EloquentRepository as Repository;

class OptionRepository extends Repository
{

    public function model()
    {
        return \Polyether\Option\Eloquent\Option::class;
    }

    public function updateOrCreate($attrs, $values)
    {
        return $this->model->updateOrCreate($attrs, $values);
    }

}
