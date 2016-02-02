<?php

namespace Etherbase\App\Repositories;

use Bosnadev\Repositories\Eloquent\Repository;

class OptionRepository extends Repository {

    public function model() {
        return '\Etherbase\App\Models\Option';
    }

    public function updateOrCreate($attrs, $values) {
        return $this->model->updateOrCreate($attrs, $values);
    }

}
