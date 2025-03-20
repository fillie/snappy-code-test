<?php

namespace App\Providers;

use App\Repositories\Contracts\PostcodeRepositoryInterface;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\Repositories\Eloquent\EloquentPostcodeRepository;
use App\Repositories\Eloquent\EloquentStoreRepository;
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

        $this->app->bind(
            StoreRepositoryInterface::class,
            EloquentStoreRepository::class
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
