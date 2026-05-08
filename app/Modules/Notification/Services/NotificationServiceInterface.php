<?php
declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\DTO\NotificationDTO;

interface NotificationServiceInterface
{
    public function sendFileNotification(NotificationDTO $dto): void;
}
