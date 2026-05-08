<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\File\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_upload_pdf_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'original_name',
                'stored_name',
                'mime_type',
                'size',
                'path',
                'expires_at',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('files', [
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf'
        ]);

        $data = $response->json();
        Storage::disk('local')->assertExists($data['path']);
    }

    public function test_can_upload_docx_file(): void
    {
        $file = UploadedFile::fake()->create('document.docx', 1024, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('files', [
            'original_name' => 'document.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    public function test_rejects_file_without_file_field(): void
    {
        $response = $this->postJson('/api/files', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $response = $this->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_file_exceeding_size_limit(): void
    {
        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf'); // 11MB

        $response = $this->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_sets_expiration_to_24_hours(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(201);

        $uploadedFile = File::first();
        $expectedExpiration = now()->addHours(24);

        $this->assertTrue(
            $uploadedFile->expires_at->diffInMinutes($expectedExpiration) < 1
        );
    }
}
