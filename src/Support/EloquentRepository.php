<?php
namespace Polyether\Support;


use Bosnadev\Repositories\Eloquent\Repository;

abstract class EloquentRepository extends Repository
{
    public function sluggable ($sluggable, $slug_col)
    {
        $slug = $this->createSlug($sluggable);

        $count = $this->model->whereRaw("{$slug_col} RLIKE '^{$sluggable}(-[0-9]+)?$'")->count();

        return $count ? "{$sluggable}-{$count}" : $slug;
    }

    public function createSlug ($sluggable)
    {
        return app('Illuminate\Support\Str')->slug($sluggable);
    }

    public function findWhereFirst ($where, $columns = ['*'], $or = false)
    {
        parent::applyCriteria();

        $model = $this->model;

        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $model = ( ! $or)
                    ? $model->where($value)
                    : $model->orWhere($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $model = ( ! $or)
                        ? $model->where($field, $operator, $search)
                        : $model->orWhere($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $model = ( ! $or)
                        ? $model->where($field, '=', $search)
                        : $model->orWhere($field, '=', $search);
                }
            } else {
                $model = ( ! $or)
                    ? $model->where($field, '=', $value)
                    : $model->orWhere($field, '=', $value);
            }
        }

        return $model->get($columns)->first();
    }

    public function findOrFail ($id)
    {
        return $this->model->findOrFail($id);
    }

    public function paginate ($perPage = 20, $columns = array('*'))
    {
        return parent::paginate($perPage, $columns);
    }
}