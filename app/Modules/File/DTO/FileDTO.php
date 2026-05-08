<?php
declare(strict_types=1);

namespace App\Modules\File\DTO;

use App\Modules\File\Models\File;
use Carbon\Carbon;

class FileDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $originalName,
        public readonly string $storedName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly string $path,
        public readonly Carbon $expiresAt,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt
    ) {}

    public static function fromModel(File $file): self
    {
        return new self(
            id: $file->id,
            originalName: $file->original_name,
            storedName: $file->stored_name,
            mimeType: $file->mime_type,
            size: $file->size,
            path: $file->path,
            expiresAt: $file->expires_at,
            createdAt: $file->created_at,
            updatedAt: $file->updated_at
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->originalName,
            'stored_name' => $this->storedName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'path' => $this->path,
            'expires_at' => $this->expiresAt->toIso8601String(),
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String()
        ];
    }
}
