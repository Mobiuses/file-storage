<?php
declare(strict_types=1);

namespace App\Modules\File\Services;

use App\Modules\File\DTO\FileDTO;
use App\Modules\File\DTO\FileUploadDTO;
use Illuminate\Database\Eloquent\Collection;

interface FileServiceInterface
{
    public function uploadFile(FileUploadDTO $dto): FileDTO;

    public function getAllFiles(): Collection;

    public function getFileById(string $id): ?FileDTO;

    public function deleteFile(string $id): bool;
}
