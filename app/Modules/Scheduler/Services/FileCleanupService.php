<?php
declare(strict_types=1);

namespace App\Modules\Scheduler\Services;

use App\Modules\File\Events\FileDeleted;
use App\Modules\File\Managers\FileStorageManager;
use App\Modules\File\Repositories\FileRepositoryInterface;
use Illuminate\Support\Facades\Log;

class FileCleanupService implements SchedulerServiceInterface
{
    public function __construct(
        protected FileRepositoryInterface $fileRepository,
        protected FileStorageManager $storageManager
    ) {}

    public function deleteExpiredFiles(): int
    {
        $expiredFiles = $this->fileRepository->findExpired();
        $deletedCount = 0;

        foreach ($expiredFiles as $file) {
            try {
                $this->storageManager->delete($file->path);

                $this->fileRepository->delete($file->id);

                event(new FileDeleted($file));

                $deletedCount++;

                Log::info('Expired file deleted', [
                    'file_id' => $file->id,
                    'original_name' => $file->original_name,
                    'expired_at' => $file->expires_at->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to delete expired file', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($deletedCount > 0) {
            Log::info('Expired files cleanup completed', [
                'deleted_count' => $deletedCount
            ]);
        }

        return $deletedCount;
    }
}
