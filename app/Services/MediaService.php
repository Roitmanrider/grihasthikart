<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function store(UploadedFile $file, string $directory, string $disk = 'uploads'): string
    {
        $extension = $file->extension();
        $safeDirectory = $this->normalizeDirectory($directory);
        $filename = Str::uuid().($extension ? '.'.$extension : '');

        return $file->storeAs($safeDirectory, $filename, $disk);
    }

    public function replace(?string $currentPath, ?UploadedFile $file, string $directory, string $disk = 'uploads'): ?string
    {
        if ($file === null) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk($disk)->delete($currentPath);
        }

        return $this->store($file, $directory, $disk);
    }

    public function delete(?string $path, string $disk = 'uploads'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'uploads/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    private function normalizeDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        return Str::startsWith($directory, 'uploads/')
            ? $directory
            : 'uploads/'.$directory;
    }
}
