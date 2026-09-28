<?php

namespace App\Jobs;

use App\Services\WeatherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshWeather implements ShouldQueue
{
    use Queueable;

    public function handle(WeatherService $weatherService): void
    {
        $weatherService->refreshPerthWeather();
    }
}
