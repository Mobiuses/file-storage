<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_delete_file(): void
    {
        $file = File::factory()->create();
        Storage::disk('local')->put($file->path, 'test content');

        Event::fake();

        $response = $this->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'File deleted successfully']);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->path);

        Event::assertDispatched(FileDeleted::class, function ($event) use ($file) {
            return $event->file->id === $file->id;
        });
    }

    public function test_returns_404_when_deleting_nonexistent_file(): void
    {
        $response = $this->deleteJson('/api/files/999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'File not found']);
    }

    public function test_dispatches_file_deleted_event(): void
    {
        $file = File::factory()->create();
        Storage::disk('local')->put($file->path, 'test content');

        Event::fake();

        $this->deleteJson("/api/files/{$file->id}");

        Event::assertDispatched(FileDeleted::class);
    }
}
