<?php
declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\File\Events\FileDeleted;
use App\Modules\Notification\DTO\NotificationDTO;
use App\Modules\Notification\Services\NotificationServiceInterface;
use Illuminate\Support\Facades\Log;

class SendFileDeletionNotification
{
    public function __construct(
        protected NotificationServiceInterface $notificationService
    ) {}

    public function handle(FileDeleted $event): void
    {
        try {
            $file = $event->file;

            $fileData = [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'stored_name' => $file->stored_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size
            ];

            $dto = NotificationDTO::create(
                action: 'deleted',
                fileData: $fileData,
                email: config('app.notification_email', env('NOTIFICATION_EMAIL'))
            );

            $this->notificationService->sendFileNotification($dto);

            Log::info('File deletion notification sent', [
                'file_id' => $file->id,
                'original_name' => $file->original_name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send file deletion notification', [
                'file_id' => $event->file->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
