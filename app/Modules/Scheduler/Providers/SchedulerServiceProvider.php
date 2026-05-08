<?php
declare(strict_types=1);

namespace App\Modules\Scheduler\Providers;

use App\Modules\Scheduler\Services\FileCleanupService;
use App\Modules\Scheduler\Services\SchedulerServiceInterface;
use Illuminate\Support\ServiceProvider;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SchedulerServiceInterface::class, FileCleanupService::class);
    }

    public function boot(): void
    {
        //
    }
}
