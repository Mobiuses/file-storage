<?php
declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\DTO\NotificationDTO;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;
use Illuminate\Support\Facades\Log;

class RabbitMQNotificationService implements NotificationServiceInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $queueName;

    public function __construct()
    {
        $this->queueName = config('rabbitmq.queues.file_notifications');
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost')
            );

            $this->channel = $this->connection->channel();

            $this->channel->queue_declare(
                $this->queueName,
                false,
                true,
                false,
                false
            );
        } catch (Exception $e) {
            throw new Exception('Failed to connect to RabbitMQ: ' . $e->getMessage());
        }
    }

    public function sendFileNotification(NotificationDTO $dto): void
    {
        try {
            $amqpMessage = new AMQPMessage(
                json_encode($dto->toArray()),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $this->channel->basic_publish(
                $amqpMessage,
                '',
                $this->queueName
            );

            Log::info('File notification sent to RabbitMQ', [
                'action' => $dto->action,
                'file_id' => $dto->fileData['id'] ?? null
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send notification to RabbitMQ: ' . $e->getMessage());
            throw $e;
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            Log::error('Error closing RabbitMQ connection: ' . $e->getMessage());
        }
    }
}
