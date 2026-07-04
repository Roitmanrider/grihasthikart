<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $extension = $file->extension();
        $filename = Str::uuid().($extension ? '.'.$extension : '');

        return $file->storeAs($directory, $filename, $disk);
    }

    public function replace(?string $currentPath, ?UploadedFile $file, string $directory, string $disk = 'public'): ?string
    {
        if ($file === null) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk($disk)->delete($currentPath);
        }

        return $this->store($file, $directory, $disk);
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
