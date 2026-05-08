<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler\Services;

use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Managers\FileStorageManager;
use App\Modules\File\Models\File;
use App\Modules\File\Repositories\FileRepositoryInterface;
use App\Modules\Scheduler\Services\FileCleanupService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class FileCleanupServiceTest extends TestCase
{
    private FileCleanupService $service;
    private FileRepositoryInterface $repository;
    private FileStorageManager $storageManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(FileRepositoryInterface::class);
        $this->storageManager = Mockery::mock(FileStorageManager::class);
        $this->service = new FileCleanupService($this->repository, $this->storageManager);

        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deletes_expired_files(): void
    {
        $uuid1 = '550e8400-e29b-41d4-a716-446655440001';
        $uuid2 = '550e8400-e29b-41d4-a716-446655440002';

        $file1 = new File([
            'id' => $uuid1,
            'original_name' => 'file1.pdf',
            'path' => 'files/file1.pdf',
            'expires_at' => now()->subHour()
        ]);
        $file1->id = $uuid1;

        $file2 = new File([
            'id' => $uuid2,
            'original_name' => 'file2.pdf',
            'path' => 'files/file2.pdf',
            'expires_at' => now()->subMinutes(30)
        ]);
        $file2->id = $uuid2;

        $expiredFiles = new \Illuminate\Database\Eloquent\Collection([$file1, $file2]);

        $this->repository
            ->shouldReceive('findExpired')
            ->once()
            ->andReturn($expiredFiles);

        $this->storageManager
            ->shouldReceive('delete')
            ->twice()
            ->andReturn(true);

        $this->repository
            ->shouldReceive('delete')
            ->with($uuid1)
            ->once()
            ->andReturn(true);

        $this->repository
            ->shouldReceive('delete')
            ->with($uuid2)
            ->once()
            ->andReturn(true);

        $count = $this->service->deleteExpiredFiles();

        $this->assertEquals(2, $count);
        Event::assertDispatched(FileDeleted::class, 2);
    }

    public function test_returns_zero_when_no_expired_files(): void
    {
        $this->repository
            ->shouldReceive('findExpired')
            ->once()
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([]));

        $count = $this->service->deleteExpiredFiles();

        $this->assertEquals(0, $count);
        Event::assertNotDispatched(FileDeleted::class);
    }

    public function test_continues_on_error_and_logs(): void
    {
        $uuid1 = '550e8400-e29b-41d4-a716-446655440001';
        $uuid2 = '550e8400-e29b-41d4-a716-446655440002';

        $file1 = new File([
            'id' => $uuid1,
            'original_name' => 'file1.pdf',
            'path' => 'files/file1.pdf',
            'expires_at' => now()->subHour()
        ]);
        $file1->id = $uuid1;

        $file2 = new File([
            'id' => $uuid2,
            'original_name' => 'file2.pdf',
            'path' => 'files/file2.pdf',
            'expires_at' => now()->subMinutes(30)
        ]);
        $file2->id = $uuid2;

        $expiredFiles = new \Illuminate\Database\Eloquent\Collection([$file1, $file2]);

        $this->repository
            ->shouldReceive('findExpired')
            ->once()
            ->andReturn($expiredFiles);

        $this->storageManager
            ->shouldReceive('delete')
            ->with('files/file1.pdf')
            ->once()
            ->andThrow(new \Exception('Storage error'));

        $this->storageManager
            ->shouldReceive('delete')
            ->with('files/file2.pdf')
            ->once()
            ->andReturn(true);

        $this->repository
            ->shouldReceive('delete')
            ->with($uuid2)
            ->once()
            ->andReturn(true);

        $count = $this->service->deleteExpiredFiles();

        $this->assertEquals(1, $count);
        Event::assertDispatched(FileDeleted::class, 1);
    }
}
