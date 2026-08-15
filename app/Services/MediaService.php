<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'shtml', 'cgi', 'pl', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm', 'svg'];

    public function store(UploadedFile $file, string $directory, string $disk = 'uploads'): string
    {
        $extension = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension()));
        $this->ensureSafeExtension($extension);
        $safeDirectory = $this->normalizeDirectory($directory);
        $filename = Str::uuid().($extension ? '.'.$extension : '');
        $this->ensureUploadDirectories($safeDirectory, $disk);

        return $file->storeAs($safeDirectory, $filename, $disk);
    }

    public function replace(?string $currentPath, ?UploadedFile $file, string $directory, string $disk = 'uploads'): ?string
    {
        if ($file === null) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk($disk)->delete($this->normalizePath($currentPath));
        }

        return $this->store($file, $directory, $disk);
    }

    public function delete(?string $path, string $disk = 'uploads'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($this->normalizePath($path));
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

        $directory = Str::startsWith($directory, 'uploads/')
            ? $directory
            : 'uploads/'.$directory;

        return $this->normalizePath($directory);
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $segments = array_values(array_filter(explode('/', $path), fn (string $segment) => $segment !== ''));

        if (
            $path === ''
            || str_contains($path, "\0")
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:\//', $path)
            || in_array('..', $segments, true)
            || ! Str::startsWith($path, 'uploads/')
        ) {
            throw new \InvalidArgumentException('Unsafe upload path.');
        }

        return implode('/', $segments);
    }

    private function ensureSafeExtension(string $extension): void
    {
        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true) || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Unsupported upload file type.');
        }
    }

    private function ensureUploadDirectories(string $targetDirectory, string $disk): void
    {
        if ($disk !== 'uploads') {
            Storage::disk($disk)->makeDirectory($targetDirectory);

            return;
        }

        foreach ([
            'uploads/categories',
            'uploads/brands',
            'uploads/products',
            'uploads/site',
            'uploads/temp',
            $targetDirectory,
        ] as $directory) {
            Storage::disk($disk)->makeDirectory($directory);
        }
    }
}
