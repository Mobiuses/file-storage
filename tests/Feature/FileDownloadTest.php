<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\File\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_download_file(): void
    {
        $file = File::factory()->create([
            'original_name' => 'document.pdf',
            'path' => 'files/test.pdf'
        ]);

        Storage::disk('local')->put($file->path, 'PDF content');

        $response = $this->get("/api/files/{$file->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=document.pdf');
    }

    public function test_returns_404_when_downloading_nonexistent_file(): void
    {
        $response = $this->getJson('/api/files/999/download');

        $response->assertStatus(404)
            ->assertJson(['error' => 'File not found']);
    }

    public function test_returns_404_when_file_record_exists_but_physical_file_missing(): void
    {
        $file = File::factory()->create([
            'path' => 'files/missing.pdf'
        ]);

        $response = $this->getJson("/api/files/{$file->id}/download");

        $response->assertStatus(404)
            ->assertJson(['error' => 'File not found in storage']);
    }
}
