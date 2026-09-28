<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('returns normalized weather from Open-Meteo', function () {
    Cache::forget('weather.perth.current.open_meteo');
    Http::preventStrayRequests();
    Http::fake([
        config('services.open_meteo.url').'*' => Http::response([
            'current' => [
                'time' => '2026-09-29T14:00',
                'temperature_2m' => 24.7,
                'apparent_temperature' => 25.2,
                'relative_humidity_2m' => 45,
                'weather_code' => 1,
                'wind_speed_10m' => 16.3,
            ],
        ]),
    ]);

    $this->getJson('/api/weather')
        ->assertOk()
        ->assertJson([
            'location' => 'Perth',
            'country' => 'Australia',
            'temperature' => 24.7,
            'feels_like' => 25.2,
            'humidity' => 45,
            'weather_code' => 1,
            'wind_speed' => 16.3,
            'observed_at' => '2026-09-29T14:00',
        ]);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), config('services.open_meteo.url'))
        && $request['latitude'] == -31.9523
        && $request['longitude'] == 115.8613
        && $request['current'] === 'temperature_2m,apparent_temperature,relative_humidity_2m,weather_code,wind_speed_10m');
});

test('caches successful weather responses', function () {
    Cache::forget('weather.perth.current.open_meteo');
    Http::preventStrayRequests();
    Http::fake([
        config('services.open_meteo.url').'*' => Http::response([
            'current' => [
                'time' => '2026-09-29T14:00',
                'temperature_2m' => 24.7,
                'apparent_temperature' => 25.2,
                'relative_humidity_2m' => 45,
                'weather_code' => 1,
                'wind_speed_10m' => 16.3,
            ],
        ]),
    ]);

    $this->getJson('/api/weather')->assertOk();
    $this->getJson('/api/weather')->assertOk();

    Http::assertSentCount(1);
});

test('returns a concise error when Open-Meteo fails', function () {
    Cache::forget('weather.perth.current.open_meteo');
    Http::preventStrayRequests();
    Http::fake([
        config('services.open_meteo.url').'*' => Http::response([], 500),
    ]);

    $this->getJson('/api/weather')
        ->assertStatus(502)
        ->assertJson(['message' => 'The weather service is currently unavailable.']);
});
