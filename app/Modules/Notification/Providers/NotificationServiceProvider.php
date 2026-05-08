<?php
declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Modules\Notification\Services\NotificationServiceInterface;
use App\Modules\Notification\Services\RabbitMQNotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationServiceInterface::class, RabbitMQNotificationService::class);
    }

    public function boot(): void
    {
        //
    }
}
