<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function rootCategories();

    public function activeCategories();

    public function featuredCategories();

    public function menuCategories();

    public function homepageCategories();
}
