<?php

namespace App\Providers;

use App\Contracts\VideoFetcher;
use App\Services\Video\YtDlpVideoFetcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoFetcher::class, YtDlpVideoFetcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
