<?php
declare(strict_types=1);

namespace App\Modules\Notification\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeNotificationsCommand extends Command
{
    protected $signature = 'rabbitmq:consume-files';
    protected $description = 'Consume file notifications from RabbitMQ';

    private AMQPStreamConnection $connection;
    private $channel;
    private string $queueName;

    public function __construct()
    {
        parent::__construct();
        $this->queueName = config('rabbitmq.queues.file_notifications');
    }

    public function handle(): int
    {
        try {
            $this->info('Starting RabbitMQ consumer for file notifications...');
            $this->connect();
            $this->consume();
            return 0;
        } catch (\Exception $e) {
            $this->error('Consumer error: ' . $e->getMessage());
            return 1;
        }
    }

    private function connect(): void
    {
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

        $this->info('Connected to RabbitMQ');
        $this->info('Waiting for messages in queue: ' . $this->queueName);
    }

    private function consume(): void
    {
        $callback = function (AMQPMessage $msg) {
            $retryCount = 0;
            $maxRetries = 3;

            if (isset($msg->get_properties()['application_headers'])) {
                $headers = $msg->get_properties()['application_headers'];
                $retryCount = $headers['x-retry-count'] ?? 0;
            }

            try {
                $data = json_decode($msg->body, true);

                $this->info('Received notification:');
                $this->line('  Action: ' . $data['action']);
                $this->line('  File ID: ' . ($data['file']['id'] ?? 'N/A'));
                $this->line('  File Name: ' . ($data['file']['original_name'] ?? 'N/A'));
                $this->line('  Email: ' . ($data['email'] ?? 'N/A'));
                $this->line('  Timestamp: ' . $data['timestamp']);

                $this->processNotification($data);

                $msg->ack();

                $this->info('Message processed successfully');
                $this->line('---');

            } catch (\Exception $e) {
                $this->error('Error processing message: ' . $e->getMessage());

                if ($retryCount < $maxRetries) {
                    $this->warn("Retry attempt {$retryCount}/{$maxRetries}");

                    $newRetryCount = $retryCount + 1;
                    $newMsg = new AMQPMessage(
                        $msg->body,
                        [
                            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                            'application_headers' => [
                                'x-retry-count' => $newRetryCount
                            ]
                        ]
                    );

                    $this->channel->basic_publish($newMsg, '', $this->queueName);
                    $msg->ack();
                } else {
                    $this->error("Max retries reached. Logging to failed_jobs table.");
                    $this->logFailedJob($msg->body, $e);
                    $msg->ack();
                }
            }
        };

        $this->channel->basic_qos(
            null,
            1,
            null
        );

        $this->channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    private function processNotification(array $data): void
    {
        /*
            Here could be implemented email sent process
        */

        Log::channel('daily')->info('File notification processed', [
            'action' => $data['action'],
            'file_id' => $data['file']['id'] ?? null,
            'file_name' => $data['file']['original_name'] ?? null,
            'email' => $data['email'] ?? null,
            'timestamp' => $data['timestamp']
        ]);

        if ($data['action'] === 'deleted') {
            $this->info('  → File deletion notification processed');
            $this->comment('  → Email would be sent to: ' . ($data['email'] ?? 'N/A'));
        }
    }

    private function logFailedJob(string $payload, \Exception $exception): void
    {
        \DB::table('failed_jobs')->insert([
            'uuid' => \Str::uuid()->toString(),
            'connection' => 'rabbitmq',
            'queue' => $this->queueName,
            'payload' => $payload,
            'exception' => $exception->getMessage() . "\n\n" . $exception->getTraceAsString(),
            'failed_at' => now(),
        ]);

        Log::channel('daily')->error('RabbitMQ job failed and logged to failed_jobs', [
            'queue' => $this->queueName,
            'payload' => $payload,
            'exception' => $exception->getMessage()
        ]);
    }

    public function __destruct()
    {
        try {
            if (isset($this->channel)) {
                $this->channel->close();
            }
            if (isset($this->connection)) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            $this->error('Error closing connection: ' . $e->getMessage());
        }
    }
}
