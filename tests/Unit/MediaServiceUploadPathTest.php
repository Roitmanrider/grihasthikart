<?php

namespace Tests\Unit;

use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServiceUploadPathTest extends TestCase
{
    public function test_uploads_disk_can_target_external_public_html_uploads_root(): void
    {
        $publicRoot = storage_path('framework/testing/hostinger-public-root');

        File::deleteDirectory($publicRoot);

        config([
            'filesystems.disks.uploads.root' => $publicRoot,
            'filesystems.disks.uploads.url' => 'http://localhost',
        ]);
        Storage::forgetDisk('uploads');

        $mediaService = app(MediaService::class);
        $path = $mediaService->store(UploadedFile::fake()->image('category.jpg'), 'categories');

        $this->assertStringStartsWith('uploads/categories/', $path);
        $this->assertFileExists($publicRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));
        $this->assertDirectoryExists($publicRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'brands');
        $this->assertDirectoryExists($publicRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products');
        $this->assertDirectoryExists($publicRoot.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'site');
        $this->assertSame(asset($path), $mediaService->url($path));

        File::deleteDirectory($publicRoot);
        Storage::forgetDisk('uploads');
    }
}
