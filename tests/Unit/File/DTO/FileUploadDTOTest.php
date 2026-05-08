<?php

declare(strict_types=1);

namespace Tests\Unit\File\DTO;

use App\Modules\File\DTO\FileUploadDTO;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileUploadDTOTest extends TestCase
{
    public function test_can_create_from_uploaded_file(): void
    {
        $uploadedFile = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $dto = FileUploadDTO::fromUploadedFile($uploadedFile);

        $this->assertInstanceOf(FileUploadDTO::class, $dto);
        $this->assertInstanceOf(UploadedFile::class, $dto->file);
        $this->assertEquals('test.pdf', $dto->originalName);
        $this->assertEquals('application/pdf', $dto->mimeType);
        $this->assertEquals(1024 * 1024, $dto->size); // UploadedFile::fake()->create uses KB
    }

    public function test_dto_properties_are_readonly(): void
    {
        $uploadedFile = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $dto = FileUploadDTO::fromUploadedFile($uploadedFile);

        $this->expectException(\Error::class);
        $dto->originalName = 'changed.pdf';
    }

    public function test_handles_different_file_types(): void
    {
        $pdfFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $docxFile = UploadedFile::fake()->create('document.docx', 800, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $pdfDto = FileUploadDTO::fromUploadedFile($pdfFile);
        $docxDto = FileUploadDTO::fromUploadedFile($docxFile);

        $this->assertEquals('application/pdf', $pdfDto->mimeType);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $docxDto->mimeType);
    }
}
