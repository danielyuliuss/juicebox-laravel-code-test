<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class WeatherService
{
    /**
     * Get the current weather for Perth, using a cached result when available.
     *
     * @return array<string, int|float|string>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    public function currentPerthWeather(): array
    {
        return Cache::remember('weather.perth.current.open_meteo', now()->addMinutes(15), function (): array {
            $response = Http::timeout(10)
                ->connectTimeout(3)
                ->get(config('services.open_meteo.url'), [
                    'latitude' => config('services.open_meteo.latitude'),
                    'longitude' => config('services.open_meteo.longitude'),
                    'current' => implode(',', [
                        'temperature_2m',
                        'apparent_temperature',
                        'relative_humidity_2m',
                        'weather_code',
                        'wind_speed_10m',
                    ]),
                    'temperature_unit' => 'celsius',
                    'wind_speed_unit' => 'kmh',
                    'timezone' => config('services.open_meteo.timezone'),
                ])
                ->throw();

            $current = data_get($response->json(), 'current');

            if (
                ! is_array($current)
                || ! is_numeric(data_get($current, 'temperature_2m'))
                || ! is_numeric(data_get($current, 'apparent_temperature'))
                || ! is_numeric(data_get($current, 'relative_humidity_2m'))
                || ! is_numeric(data_get($current, 'weather_code'))
                || ! is_numeric(data_get($current, 'wind_speed_10m'))
                || ! is_string(data_get($current, 'time'))
            ) {
                throw new UnexpectedValueException('The weather provider returned an invalid response.');
            }

            return [
                'location' => 'Perth',
                'country' => 'Australia',
                'temperature' => data_get($current, 'temperature_2m'),
                'feels_like' => data_get($current, 'apparent_temperature'),
                'humidity' => data_get($current, 'relative_humidity_2m'),
                'weather_code' => data_get($current, 'weather_code'),
                'wind_speed' => data_get($current, 'wind_speed_10m'),
                'observed_at' => data_get($current, 'time'),
            ];
        });
    }
}
