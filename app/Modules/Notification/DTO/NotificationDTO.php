<?php
declare(strict_types=1);

namespace App\Modules\Notification\DTO;

class NotificationDTO
{
    public function __construct(
        public readonly string $action,
        public readonly array $fileData,
        public readonly string $email,
        public readonly string $timestamp
    ) {}

    public static function create(string $action, array $fileData, string $email): self
    {
        return new self(
            action: $action,
            fileData: $fileData,
            email: $email,
            timestamp: now()->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'file' => $this->fileData,
            'email' => $this->email,
            'timestamp' => $this->timestamp
        ];
    }
}
