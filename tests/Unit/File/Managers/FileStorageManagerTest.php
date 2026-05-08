<?php

declare(strict_types=1);

namespace Tests\Unit\File\Managers;

use App\Modules\File\Managers\FileStorageManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageManagerTest extends TestCase
{
    private FileStorageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->manager = new FileStorageManager();
    }

    public function test_can_store_file(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);

        $path = $this->manager->store($file);

        $this->assertStringStartsWith('files/', $path);
        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_generates_unique_name(): void
    {
        $name1 = $this->manager->generateUniqueName('test.pdf');
        $name2 = $this->manager->generateUniqueName('test.pdf');

        $this->assertNotEquals($name1, $name2);
        $this->assertStringEndsWith('.pdf', $name1);
        $this->assertStringEndsWith('.pdf', $name2);
    }

    public function test_generated_name_is_slugified(): void
    {
        $name = $this->manager->generateUniqueName('Test File With Spaces.pdf');

        $this->assertStringContainsString('test-file-with-spaces', $name);
        $this->assertStringEndsWith('.pdf', $name);
    }

    public function test_can_check_file_exists(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        $path = $this->manager->store($file);

        $this->assertTrue($this->manager->exists($path));
        $this->assertFalse($this->manager->exists('files/nonexistent.pdf'));
    }

    public function test_can_delete_file(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        $path = $this->manager->store($file);

        $this->assertTrue($this->manager->exists($path));

        $result = $this->manager->delete($path);

        $this->assertTrue($result);
        $this->assertFalse($this->manager->exists($path));
    }

    public function test_delete_returns_false_for_nonexistent_file(): void
    {
        $result = $this->manager->delete('files/nonexistent.pdf');

        $this->assertFalse($result);
    }

    public function test_can_get_full_path(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        $path = $this->manager->store($file);

        $fullPath = $this->manager->getFullPath($path);

        $this->assertStringContainsString('storage', $fullPath);
        $this->assertStringEndsWith('.pdf', $fullPath);
    }
}
