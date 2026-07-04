<?php

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function all();

    public function paginate(int $perPage = 15);

    public function find(int $id);

    public function create(array $data);

    public function update(Model|int $record, array $data);

    public function delete(Model|int $record);
}
