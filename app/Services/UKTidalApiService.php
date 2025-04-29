<?php

/**
 * UK Tidal API Service
 * Path: app/Services/UKTidalApiService.php
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UKTidalApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.uk_tidal_api.base_url', 'https://admiraltyapi.azure-api.net/uktidalapi');
        $this->apiKey = config('services.uk_tidal_api.key');
    }

    /**
     * Get all tidal stations
     *
     * @return array|null
     */
    public function getAllStations(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/api/V1/Stations");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch UK tidal stations', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception while fetching UK tidal stations', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get a single tidal station by ID
     *
     * @param string $stationId
     * @return array|null
     */
    public function getStation(string $stationId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/api/V1/Stations/{$stationId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to fetch UK tidal station: {$stationId}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching UK tidal station: {$stationId}", [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get tidal events for a station
     *
     * @param string $stationId
     * @param int $duration Days (1-7)
     * @return array|null
     */
    public function getTidalEvents(string $stationId, int $duration = 7): ?array
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
            ])->get("{$this->baseUrl}/api/V1/Stations/{$stationId}/TidalEvents", [
                'duration' => $duration,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to fetch tidal events for station: {$stationId}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching tidal events for station: {$stationId}", [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
