<?php
declare(strict_types=1);

namespace App\Modules\File\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageManager
{
    private const STORAGE_DISK = 'local';
    private const FILES_DIRECTORY = 'files';

    public function store(UploadedFile $file): string
    {
        $storedName = $this->generateUniqueName($file->getClientOriginalName());
        $path = $file->storeAs(self::FILES_DIRECTORY, $storedName, self::STORAGE_DISK);

        return $path;
    }

    public function delete(string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }

        return Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk(self::STORAGE_DISK)->exists($path);
    }

    public function generateUniqueName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitized = Str::slug($filename);
        $unique = Str::random(16);

        return "{$sanitized}_{$unique}.{$extension}";
    }

    public function getFullPath(string $path): string
    {
        return Storage::disk(self::STORAGE_DISK)->path($path);
    }

    public function download(string $path, string $originalName)
    {
        return Storage::disk(self::STORAGE_DISK)->download($path, $originalName);
    }
}
