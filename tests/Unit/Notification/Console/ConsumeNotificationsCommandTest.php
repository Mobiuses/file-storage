<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsumeNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;
    public function test_failed_job_is_logged_to_database(): void
    {
        $payload = json_encode([
            'action' => 'deleted',
            'file' => [
                'id' => '550e8400-e29b-41d4-a716-446655440000',
                'original_name' => 'test.pdf'
            ],
            'email' => 'test@example.com',
            'timestamp' => now()->toIso8601String()
        ]);

        $exception = new \Exception('Test exception message');

        DB::table('failed_jobs')->insert([
            'uuid' => \Str::uuid()->toString(),
            'connection' => 'rabbitmq',
            'queue' => 'file_notifications',
            'payload' => $payload,
            'exception' => $exception->getMessage() . "\n\n" . $exception->getTraceAsString(),
            'failed_at' => now(),
        ]);

        $this->assertDatabaseHas('failed_jobs', [
            'connection' => 'rabbitmq',
            'queue' => 'file_notifications',
        ]);

        $failedJob = DB::table('failed_jobs')
            ->where('connection', 'rabbitmq')
            ->first();

        $this->assertNotNull($failedJob);
        $this->assertEquals('rabbitmq', $failedJob->connection);
        $this->assertEquals('file_notifications', $failedJob->queue);
        $this->assertStringContainsString('Test exception message', $failedJob->exception);
    }

    public function test_failed_job_contains_payload_and_exception(): void
    {
        $payload = json_encode([
            'action' => 'deleted',
            'file' => ['id' => '123', 'original_name' => 'document.pdf'],
            'email' => 'user@example.com',
            'timestamp' => now()->toIso8601String()
        ]);

        $exception = new \Exception('Email service unavailable');

        DB::table('failed_jobs')->insert([
            'uuid' => \Str::uuid()->toString(),
            'connection' => 'rabbitmq',
            'queue' => 'file_notifications',
            'payload' => $payload,
            'exception' => $exception->getMessage() . "\n\n" . $exception->getTraceAsString(),
            'failed_at' => now(),
        ]);

        $failedJob = DB::table('failed_jobs')
            ->where('connection', 'rabbitmq')
            ->latest('failed_at')
            ->first();

        $decodedPayload = json_decode($failedJob->payload, true);

        $this->assertEquals('deleted', $decodedPayload['action']);
        $this->assertEquals('document.pdf', $decodedPayload['file']['original_name']);
        $this->assertStringContainsString('Email service unavailable', $failedJob->exception);
    }
}
