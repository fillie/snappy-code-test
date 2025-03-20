<?php

namespace App\Providers;

use App\Repositories\Contracts\PostcodeRepositoryInterface;
use App\Repositories\Eloquent\EloquentPostcodeRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PostcodeRepositoryInterface::class,
            EloquentPostcodeRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
