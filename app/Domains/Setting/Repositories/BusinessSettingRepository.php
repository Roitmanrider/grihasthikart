<?php

namespace App\Domains\Setting\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Setting\Contracts\BusinessSettingRepositoryInterface;
use App\Models\BusinessSetting;

class BusinessSettingRepository extends BaseRepository implements BusinessSettingRepositoryInterface
{
    public function __construct(BusinessSetting $model)
    {
        parent::__construct($model);
    }

    public function findByKey(string $key): ?BusinessSetting
    {
        [$group, $settingKey] = explode('.', $key, 2);

        return $this->model->newQuery()
            ->where('group', $group)
            ->where('key', $settingKey)
            ->first();
    }

    public function group(string $group)
    {
        return $this->model->newQuery()
            ->where('group', $group)
            ->orderBy('display_order')
            ->orderBy('label')
            ->get();
    }

    public function updateByKey(string $key, mixed $value): BusinessSetting
    {
        $setting = $this->findByKey($key);

        if (! $setting) {
            [$group, $settingKey] = explode('.', $key, 2);
            $setting = $this->model->newQuery()->create([
                'group' => $group,
                'key' => $settingKey,
                'value' => null,
            ]);
        }

        $setting->update(['value' => $value]);

        return $setting;
    }
}
