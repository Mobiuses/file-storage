<?php
declare(strict_types=1);

namespace App\Modules\File\Events;

use App\Modules\File\Models\File;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public File $file
    ) {}
}
