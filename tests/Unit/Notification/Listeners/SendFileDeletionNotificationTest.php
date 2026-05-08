<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Listeners;

use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Models\File;
use App\Modules\Notification\Listeners\SendFileDeletionNotification;
use App\Modules\Notification\Services\NotificationServiceInterface;
use Mockery;
use Tests\TestCase;

class SendFileDeletionNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handles_file_deleted_event(): void
    {
        $file = new File([
            'id' => 1,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024
        ]);
        $file->id = 1;

        $event = new FileDeleted($file);

        $notificationService = Mockery::mock(NotificationServiceInterface::class);
        $notificationService
            ->shouldReceive('sendFileNotification')
            ->once()
            ->withArgs(function ($dto) use ($file) {
                return $dto->action === 'deleted'
                    && $dto->fileData['id'] === $file->id
                    && $dto->fileData['original_name'] === $file->original_name;
            });

        $listener = new SendFileDeletionNotification($notificationService);
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_handles_exception_gracefully(): void
    {
        $file = new File([
            'id' => 1,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_abc123.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024
        ]);
        $file->id = 1;

        $event = new FileDeleted($file);

        $notificationService = Mockery::mock(NotificationServiceInterface::class);
        $notificationService
            ->shouldReceive('sendFileNotification')
            ->once()
            ->andThrow(new \Exception('RabbitMQ connection failed'));

        $listener = new SendFileDeletionNotification($notificationService);

        // Should not throw exception
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
