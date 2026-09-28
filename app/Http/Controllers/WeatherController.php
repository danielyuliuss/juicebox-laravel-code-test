<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use UnexpectedValueException;

class WeatherController extends Controller
{
    public function show(WeatherService $weatherService): JsonResponse
    {
        try {
            return response()->json($weatherService->currentPerthWeather());
        } catch (ConnectionException|RequestException|UnexpectedValueException) {
            return response()->json([
                'message' => 'The weather service is currently unavailable.',
            ], 502);
        }
    }
}
