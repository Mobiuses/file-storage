<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Module service providers will register their own bindings
    }

    public function boot(): void
    {
        //
    }
}
