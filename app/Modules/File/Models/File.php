<?php
declare(strict_types=1);

namespace App\Modules\File\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'original_name',
        'stored_name',
        'mime_type',
        'size',
        'path',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'size' => 'integer'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return FileFactory::new();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
