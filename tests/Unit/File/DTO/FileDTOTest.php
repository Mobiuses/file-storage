<?php

declare(strict_types=1);

namespace Tests\Unit\File\DTO;

use App\Modules\File\DTO\FileDTO;
use App\Modules\File\Models\File;
use Carbon\Carbon;
use Tests\TestCase;

class FileDTOTest extends TestCase
{
    public function test_can_create_from_model(): void
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addHours(24);

        $file = new File([
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'path' => 'files/test_abc123.pdf',
            'expires_at' => $expiresAt,
        ]);
        $file->id = '550e8400-e29b-41d4-a716-446655440000';
        $file->created_at = $now;
        $file->updated_at = $now;

        $dto = FileDTO::fromModel($file);

        $this->assertInstanceOf(FileDTO::class, $dto);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        $this->assertEquals('test.pdf', $dto->originalName);
        $this->assertEquals('test_abc123.pdf', $dto->storedName);
        $this->assertEquals('application/pdf', $dto->mimeType);
        $this->assertEquals(1024, $dto->size);
        $this->assertEquals('files/test_abc123.pdf', $dto->path);
        $this->assertInstanceOf(Carbon::class, $dto->expiresAt);
        $this->assertInstanceOf(Carbon::class, $dto->createdAt);
        $this->assertInstanceOf(Carbon::class, $dto->updatedAt);
    }

    public function test_can_convert_to_array(): void
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addHours(24);

        $dto = new FileDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            originalName: 'test.pdf',
            storedName: 'test_abc123.pdf',
            mimeType: 'application/pdf',
            size: 1024,
            path: 'files/test_abc123.pdf',
            expiresAt: $expiresAt,
            createdAt: $now,
            updatedAt: $now
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('test.pdf', $array['original_name']);
        $this->assertEquals('test_abc123.pdf', $array['stored_name']);
        $this->assertEquals('application/pdf', $array['mime_type']);
        $this->assertEquals(1024, $array['size']);
        $this->assertEquals('files/test_abc123.pdf', $array['path']);
        $this->assertIsString($array['expires_at']);
        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
    }

    public function test_dto_is_readonly(): void
    {
        $dto = new FileDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            originalName: 'test.pdf',
            storedName: 'test_abc123.pdf',
            mimeType: 'application/pdf',
            size: 1024,
            path: 'files/test_abc123.pdf',
            expiresAt: Carbon::now()->addHours(24),
            createdAt: Carbon::now(),
            updatedAt: Carbon::now()
        );

        $this->expectException(\Error::class);
        $dto->id = '00000000-0000-0000-0000-000000000000';
    }
}
