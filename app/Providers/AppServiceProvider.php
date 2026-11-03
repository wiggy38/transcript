<?php

namespace App\Providers;

use App\Contracts\AudioExtractor;
use App\Contracts\VideoFetcher;
use App\Services\Audio\FfmpegAudioExtractor;
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
        $this->app->bind(AudioExtractor::class, FfmpegAudioExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
