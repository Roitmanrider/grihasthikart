<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Category;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }
}
