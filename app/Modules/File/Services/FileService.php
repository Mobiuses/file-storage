<?php
declare(strict_types=1);

namespace App\Modules\File\Services;

use App\Modules\File\DTO\FileDTO;
use App\Modules\File\DTO\FileUploadDTO;
use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Events\FileUploaded;
use App\Modules\File\Managers\FileStorageManager;
use App\Modules\File\Repositories\FileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class FileService implements FileServiceInterface
{
    public function __construct(
        protected FileRepositoryInterface $repository,
        protected FileStorageManager $storageManager
    ) {}

    public function uploadFile(FileUploadDTO $dto): FileDTO
    {
        $path = $this->storageManager->store($dto->file);
        $storedName = basename($path);

        $fileData = [
            'original_name' => $dto->originalName,
            'stored_name' => $storedName,
            'mime_type' => $dto->mimeType,
            'size' => $dto->size,
            'path' => $path,
            'expires_at' => now()->addHours(config('filesystems.file_expiration_hours'))
//            'expires_at' => now()->addMinute() // Uncomment for testing purpose
        ];

        $file = $this->repository->create($fileData);

        event(new FileUploaded($file));

        Log::info('File uploaded', [
            'file_id' => $file->id,
            'original_name' => $file->original_name
        ]);

        return FileDTO::fromModel($file);
    }

    public function getAllFiles(): Collection
    {
        return $this->repository->all();
    }

    public function getFileById(string $id): ?FileDTO
    {
        $file = $this->repository->find($id);

        return $file ? FileDTO::fromModel($file) : null;
    }

    public function deleteFile(string $id): bool
    {
        $file = $this->repository->find($id);

        if (!$file) {
            return false;
        }

        $this->storageManager->delete($file->path);

        $deleted = $this->repository->delete($id);

        if ($deleted) {
            event(new FileDeleted($file));

            Log::info('File deleted', [
                'file_id' => $file->id,
                'original_name' => $file->original_name
            ]);
        }

        return $deleted;
    }
}
