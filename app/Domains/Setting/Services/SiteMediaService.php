<?php

namespace App\Domains\Setting\Services;

use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Throwable;

class SiteMediaService
{
    public function __construct(
        private readonly BusinessSettingService $settingService,
        private readonly MediaService $mediaService
    ) {}

    public function settings(): array
    {
        return [
            'splash_image_path' => $this->settingService->get('site.splash_image_path'),
            'loading_image_path' => $this->settingService->get('site.loading_image_path'),
        ];
    }

    public function update(array $data): void
    {
        foreach (['splash_image' => 'splash_image_path', 'loading_image' => 'loading_image_path'] as $input => $settingKey) {
            if (($data[$input] ?? null) instanceof UploadedFile) {
                $oldPath = $this->settingService->get('site.'.$settingKey);
                $newPath = $this->mediaService->store($data[$input], 'site');

                try {
                    $this->settingService->set('site.'.$settingKey, $newPath);
                } catch (Throwable $exception) {
                    $this->mediaService->delete($newPath);

                    throw $exception;
                }

                $this->mediaService->delete($oldPath);
            }
        }

        foreach (['remove_splash_image' => 'splash_image_path', 'remove_loading_image' => 'loading_image_path'] as $input => $settingKey) {
            if ((bool) ($data[$input] ?? false)) {
                $oldPath = $this->settingService->get('site.'.$settingKey);
                $this->settingService->set('site.'.$settingKey, null);
                $this->mediaService->delete($oldPath);
            }
        }
    }
}
