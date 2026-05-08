<?php
declare(strict_types=1);

namespace App\Modules\Scheduler\Services;

interface SchedulerServiceInterface
{
    public function deleteExpiredFiles(): int;
}
