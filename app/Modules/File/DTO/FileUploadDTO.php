<?php
declare(strict_types=1);

namespace App\Modules\File\DTO;

use Illuminate\Http\UploadedFile;

class FileUploadDTO
{
    public function __construct(
        public readonly UploadedFile $file,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size
    ) {}

    public static function fromUploadedFile(UploadedFile $file): self
    {
        return new self(
            file: $file,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
            size: $file->getSize()
        );
    }
}
