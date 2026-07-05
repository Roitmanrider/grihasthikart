<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    public function generate(
        string $source,
        string $modelClass,
        ?int $ignoreId = null,
        string $column = 'slug',
        int $collisionAttempt = 0
    ): string {
        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = Str::lower(Str::random(8));
        }

        if ($collisionAttempt > 0) {
            $baseSlug .= '-'.Str::lower(Str::random(6));
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->exists($modelClass, $slug, $ignoreId, $column)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function generateScoped(
        string $source,
        string $modelClass,
        array $scope,
        ?int $ignoreId = null,
        string $column = 'slug',
        int $collisionAttempt = 0
    ): string {
        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = Str::lower(Str::random(8));
        }

        if ($collisionAttempt > 0) {
            $baseSlug .= '-'.Str::lower(Str::random(6));
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->exists($modelClass, $slug, $ignoreId, $column, $scope)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function exists(string $modelClass, string $slug, ?int $ignoreId, string $column, array $scope = []): bool
    {
        /** @var Model $model */
        $model = new $modelClass();

        $query = $model->newQuery()
            ->where($column, $slug);

        foreach ($scope as $scopeColumn => $scopeValue) {
            $query->where($scopeColumn, $scopeValue);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
