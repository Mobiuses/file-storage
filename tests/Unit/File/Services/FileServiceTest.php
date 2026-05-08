<?php

declare(strict_types=1);

namespace Tests\Unit\File\Services;

use App\Modules\File\DTO\FileDTO;
use App\Modules\File\DTO\FileUploadDTO;
use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Events\FileUploaded;
use App\Modules\File\Managers\FileStorageManager;
use App\Modules\File\Models\File;
use App\Modules\File\Repositories\FileRepositoryInterface;
use App\Modules\File\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class FileServiceTest extends TestCase
{
    private FileService $service;
    private FileRepositoryInterface $repository;
    private FileStorageManager $storageManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(FileRepositoryInterface::class);
        $this->storageManager = Mockery::mock(FileStorageManager::class);
        $this->service = new FileService($this->repository, $this->storageManager);

        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_upload_file(): void
    {
        $uploadedFile = UploadedFile::fake()->create('test.pdf', 1024);
        $dto = FileUploadDTO::fromUploadedFile($uploadedFile);

        $this->storageManager
            ->shouldReceive('store')
            ->once()
            ->with($uploadedFile)
            ->andReturn('files/test_abc123.pdf');

        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $file = new File([
            'id' => $uuid,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024 * 1024,
            'path' => 'files/test_abc123.pdf',
            'expires_at' => now()->addHours(24),
        ]);
        $file->id = $uuid;
        $file->created_at = now();
        $file->updated_at = now();

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($file);

        $result = $this->service->uploadFile($dto);

        $this->assertInstanceOf(FileDTO::class, $result);
        $this->assertEquals('test.pdf', $result->originalName);
        Event::assertDispatched(FileUploaded::class);
    }

    public function test_can_get_all_files(): void
    {
        $files = new \Illuminate\Database\Eloquent\Collection([
            new File(['id' => 1, 'original_name' => 'file1.pdf']),
            new File(['id' => 2, 'original_name' => 'file2.pdf'])
        ]);

        $this->repository
            ->shouldReceive('all')
            ->once()
            ->andReturn($files);

        $result = $this->service->getAllFiles();

        $this->assertCount(2, $result);
    }

    public function test_can_get_file_by_id(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $file = new File([
            'id' => $uuid,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'path' => 'files/test_abc123.pdf',
            'expires_at' => now()->addHours(24),
        ]);
        $file->id = $uuid;
        $file->created_at = now();
        $file->updated_at = now();

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($uuid)
            ->andReturn($file);

        $result = $this->service->getFileById($uuid);

        $this->assertInstanceOf(FileDTO::class, $result);
        $this->assertEquals('test.pdf', $result->originalName);
    }

    public function test_get_file_by_id_returns_null_when_not_found(): void
    {
        $uuid = '00000000-0000-0000-0000-000000000000';
        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($uuid)
            ->andReturn(null);

        $result = $this->service->getFileById($uuid);

        $this->assertNull($result);
    }

    public function test_can_delete_file(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $file = new File([
            'id' => $uuid,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'path' => 'files/test_abc123.pdf',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $file->id = $uuid;

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($uuid)
            ->andReturn($file);

        $this->storageManager
            ->shouldReceive('delete')
            ->once()
            ->with('files/test_abc123.pdf')
            ->andReturn(true);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($uuid)
            ->andReturn(true);

        $result = $this->service->deleteFile($uuid);

        $this->assertTrue($result);
        Event::assertDispatched(FileDeleted::class);
    }

    public function test_delete_returns_false_when_file_not_found(): void
    {
        $uuid = '00000000-0000-0000-0000-000000000000';
        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($uuid)
            ->andReturn(null);

        $result = $this->service->deleteFile($uuid);

        $this->assertFalse($result);
        Event::assertNotDispatched(FileDeleted::class);
    }
}
