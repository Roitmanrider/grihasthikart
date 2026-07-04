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

    public function update(Model|int $record, array $data)
    {
        $record = $this->resolveRecord($record);

        $record->update($data);

        return $record;
    }

    public function delete(Model|int $record)
    {
        return $this->resolveRecord($record)->delete();
    }

    protected function resolveRecord(Model|int $record): Model
    {
        if ($record instanceof Model) {
            return $record;
        }

        return $this->find($record);
    }
}
