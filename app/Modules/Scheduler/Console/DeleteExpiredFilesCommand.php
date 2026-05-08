<?php
declare(strict_types=1);

namespace App\Modules\Scheduler\Console;

use App\Modules\Scheduler\Services\SchedulerServiceInterface;
use Illuminate\Console\Command;

class DeleteExpiredFilesCommand extends Command
{
    protected $signature = 'files:delete-expired';
    protected $description = 'Delete expired files (older than 24 hours)';

    public function __construct(
        protected SchedulerServiceInterface $schedulerService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting expired files cleanup...');

        try {
            $deletedCount = $this->schedulerService->deleteExpiredFiles();

            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} expired file(s)");
            } else {
                $this->info('No expired files found');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            return 1;
        }
    }
}
