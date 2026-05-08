<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\File\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileListTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_files(): void
    {
        File::factory()->count(3)->create();

        $response = $this->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_returns_empty_array_when_no_files(): void
    {
        $response = $this->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_returns_files_with_correct_structure(): void
    {
        File::factory()->create();

        $response = $this->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'original_name',
                    'stored_name',
                    'mime_type',
                    'size',
                    'path',
                    'expires_at',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_can_get_single_file(): void
    {
        $file = File::factory()->create();

        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $file->id,
                'original_name' => $file->original_name
            ]);
    }

    public function test_returns_404_for_nonexistent_file(): void
    {
        $response = $this->getJson('/api/files/999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'File not found']);
    }
}
