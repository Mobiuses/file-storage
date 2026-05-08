<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Services;

use App\Modules\Notification\DTO\NotificationDTO;
use Tests\TestCase;

class RabbitMQNotificationServiceTest extends TestCase
{
    public function test_notification_dto_structure(): void
    {
        $dto = NotificationDTO::create(
            action: 'deleted',
            fileData: ['id' => 1, 'original_name' => 'test.pdf'],
            email: 'test@example.com'
        );

        $array = $dto->toArray();

        $this->assertEquals('deleted', $array['action']);
        $this->assertEquals(1, $array['file']['id']);
        $this->assertEquals('test.pdf', $array['file']['original_name']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertArrayHasKey('timestamp', $array);
    }

    // Note: Full RabbitMQ integration test would require actual RabbitMQ connection
    // This is tested in Feature tests with real infrastructure
}
