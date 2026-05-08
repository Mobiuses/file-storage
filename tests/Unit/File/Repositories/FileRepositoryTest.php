<?php

declare(strict_types=1);

namespace Tests\Unit\File\Repositories;

use App\Modules\File\Models\File;
use App\Modules\File\Repositories\FileRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private FileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new FileRepository(new File());
    }

    public function test_can_create_file(): void
    {
        $data = [
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'path' => 'files/test_abc123.pdf',
            'expires_at' => Carbon::now()->addHours(24)
        ];

        $file = $this->repository->create($data);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('test.pdf', $file->original_name);
        $this->assertDatabaseHas('files', ['original_name' => 'test.pdf']);
    }

    public function test_can_find_file_by_id(): void
    {
        $file = File::factory()->create();

        $found = $this->repository->find($file->id);

        $this->assertInstanceOf(File::class, $found);
        $this->assertEquals($file->id, $found->id);
    }

    public function test_find_returns_null_for_nonexistent_id(): void
    {
        $found = $this->repository->find('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    public function test_can_get_all_files(): void
    {
        File::factory()->count(3)->create();

        $files = $this->repository->all();

        $this->assertCount(3, $files);
    }

    public function test_all_returns_files_ordered_by_created_at_desc(): void
    {
        $file1 = File::factory()->create(['created_at' => Carbon::now()->subHours(2)]);
        $file2 = File::factory()->create(['created_at' => Carbon::now()->subHours(1)]);
        $file3 = File::factory()->create(['created_at' => Carbon::now()]);

        $files = $this->repository->all();

        $this->assertEquals($file3->id, $files[0]->id);
        $this->assertEquals($file2->id, $files[1]->id);
        $this->assertEquals($file1->id, $files[2]->id);
    }

    public function test_can_delete_file(): void
    {
        $file = File::factory()->create();

        $result = $this->repository->delete($file->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_delete_returns_false_for_nonexistent_id(): void
    {
        $result = $this->repository->delete('00000000-0000-0000-0000-000000000000');

        $this->assertFalse($result);
    }

    public function test_can_find_expired_files(): void
    {
        File::factory()->create(['expires_at' => Carbon::now()->subHour()]);
        File::factory()->create(['expires_at' => Carbon::now()->subMinutes(30)]);
        File::factory()->create(['expires_at' => Carbon::now()->addHour()]);

        $expired = $this->repository->findExpired();

        $this->assertCount(2, $expired);
    }

    public function test_find_expired_returns_empty_collection_when_no_expired_files(): void
    {
        File::factory()->count(3)->create(['expires_at' => Carbon::now()->addHours(24)]);

        $expired = $this->repository->findExpired();

        $this->assertCount(0, $expired);
    }
}
