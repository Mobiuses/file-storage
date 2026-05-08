<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Models\File;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteExpiredFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_deletes_expired_files(): void
    {
        $expiredFile1 = File::factory()->create(['expires_at' => Carbon::now()->subHour()]);
        $expiredFile2 = File::factory()->create(['expires_at' => Carbon::now()->subMinutes(30)]);
        $activeFile = File::factory()->create(['expires_at' => Carbon::now()->addHours(24)]);

        Storage::disk('local')->put($expiredFile1->path, 'content1');
        Storage::disk('local')->put($expiredFile2->path, 'content2');
        Storage::disk('local')->put($activeFile->path, 'content3');

        Event::fake();

        $this->artisan('files:delete-expired')
            ->expectsOutput('Starting expired files cleanup...')
            ->expectsOutput('Successfully deleted 2 expired file(s)')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('files', ['id' => $expiredFile1->id]);
        $this->assertDatabaseMissing('files', ['id' => $expiredFile2->id]);
        $this->assertDatabaseHas('files', ['id' => $activeFile->id]);

        Storage::disk('local')->assertMissing($expiredFile1->path);
        Storage::disk('local')->assertMissing($expiredFile2->path);
        Storage::disk('local')->assertExists($activeFile->path);

        Event::assertDispatched(FileDeleted::class, 2);
    }

    public function test_shows_message_when_no_expired_files(): void
    {
        File::factory()->count(3)->create(['expires_at' => Carbon::now()->addHours(24)]);

        $this->artisan('files:delete-expired')
            ->expectsOutput('Starting expired files cleanup...')
            ->expectsOutput('No expired files found')
            ->assertExitCode(0);
    }

    public function test_dispatches_file_deleted_event_for_each_file(): void
    {
        $expiredFile1 = File::factory()->create(['expires_at' => Carbon::now()->subHour()]);
        $expiredFile2 = File::factory()->create(['expires_at' => Carbon::now()->subMinutes(30)]);

        Storage::disk('local')->put($expiredFile1->path, 'content1');
        Storage::disk('local')->put($expiredFile2->path, 'content2');

        Event::fake();

        $this->artisan('files:delete-expired');

        Event::assertDispatched(FileDeleted::class, function ($event) use ($expiredFile1) {
            return $event->file->id === $expiredFile1->id;
        });

        Event::assertDispatched(FileDeleted::class, function ($event) use ($expiredFile2) {
            return $event->file->id === $expiredFile2->id;
        });
    }

    public function test_handles_errors_gracefully(): void
    {
        $expiredFile = File::factory()->create([
            'expires_at' => Carbon::now()->subHour(),
            'path' => 'files/nonexistent.pdf'
        ]);

        // File doesn't exist in storage, but command should not crash

        $this->artisan('files:delete-expired')
            ->assertExitCode(0);

        // File should still be deleted from database
        $this->assertDatabaseMissing('files', ['id' => $expiredFile->id]);
    }
}
