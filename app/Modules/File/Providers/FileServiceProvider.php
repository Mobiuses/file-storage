<?php
declare(strict_types=1);

namespace App\Modules\File\Providers;

use App\Modules\File\Repositories\FileRepository;
use App\Modules\File\Repositories\FileRepositoryInterface;
use App\Modules\File\Services\FileService;
use App\Modules\File\Services\FileServiceInterface;
use Illuminate\Support\ServiceProvider;

class FileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(FileServiceInterface::class, FileService::class);
    }

    public function boot(): void
    {
        //
    }
}
