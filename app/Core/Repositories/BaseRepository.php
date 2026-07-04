<?php

namespace App\Core\Repositories;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model
            ->latest()
            ->get();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->find($id);

        $record->update($data);

        return $record;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }
}
