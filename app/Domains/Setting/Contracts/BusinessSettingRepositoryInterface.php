<?php

namespace App\Domains\Setting\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\BusinessSetting;

interface BusinessSettingRepositoryInterface extends RepositoryInterface
{
    public function findByKey(string $key): ?BusinessSetting;

    public function group(string $group);

    public function updateByKey(string $key, mixed $value): BusinessSetting;
}
