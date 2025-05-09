<?php

/**
 * Fetch Tidal Stations Action
 * Path: app/Actions/FetchTidalStationsAction.php
 */

namespace App\Actions;

use Exception;
use App\Models\TidalStation;
use Illuminate\Support\Facades\DB;
use App\Services\UKTidalApiService;
use Illuminate\Support\Facades\Log;

class FetchTidalStationsAction
{
    protected UKTidalApiService $tidalApiService;

    public function __construct(UKTidalApiService $tidalApiService)
    {
        $this->tidalApiService = $tidalApiService;
    }

    /**
     * Execute the action to fetch and store all tidal stations
     *
     * @return array Summary of the operation
     */
    public function execute(): array
    {
        $startTime = microtime(true);
        $stationsData = $this->tidalApiService->getAllStations();

        if (!$stationsData || !isset($stationsData['features']) || !is_array($stationsData['features'])) {
            Log::error('Failed to fetch tidal stations or invalid response format');
            return [
                'success' => false,
                'message' => 'Failed to fetch tidal stations or invalid response format',
                'stations_processed' => 0,
                'stations_added' => 0,
                'stations_updated' => 0,
                'execution_time' => microtime(true) - $startTime,
            ];
        }

        $stationsAdded = 0;
        $stationsUpdated = 0;

        try {
            DB::beginTransaction();

            foreach ($stationsData['features'] as $feature) {
                try {
                    // Check if we have the required properties
                    if (!isset($feature['properties']) || !isset($feature['geometry'])) {
                        Log::warning('Skipping station with missing properties or geometry', ['feature' => $feature]);
                        continue;
                    }

                    $properties = $feature['properties'];

                    // The ID can be either in the feature id or in properties.Id
                    $stationId = $feature['id'] ?? $properties['Id'] ?? null;

                    if (!$stationId) {
                        Log::warning('Skipping station with no ID', ['feature' => $feature]);
                        continue;
                    }

                    // Extract coordinates from the GeoJSON Point
                    $coordinates = null;
                    if (isset($feature['geometry']['coordinates']) &&
                        is_array($feature['geometry']['coordinates']) &&
                        count($feature['geometry']['coordinates']) >= 2) {
                        $coordinates = $feature['geometry']['coordinates'];
                    }

                    $stationData = [
                        'name' => $properties['Name'] ?? '',
                        'country' => $properties['Country'] ?? null,
                        'longitude' => $coordinates ? $coordinates[0] : null,
                        'latitude' => $coordinates ? $coordinates[1] : null,
                        'continuous_heights_available' => $properties['ContinuousHeightsAvailable'] ?? false,
                        'footnote' => $properties['Footnote'] ?? null,
                        'raw_data' => $feature,
                    ];

                    // Update or create the station record
                    $station = TidalStation::updateOrCreate(
                        ['station_id' => $stationId],
                        $stationData
                    );

                    if ($station->wasRecentlyCreated) {
                        $stationsAdded++;
                    } else {
                        $stationsUpdated++;
                    }
                } catch (Exception $e) {
                    Log::error('Error processing station', [
                        'feature' => $feature,
                        'message' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'stations_processed' => count($stationsData['features']),
                'stations_added' => $stationsAdded,
                'stations_updated' => $stationsUpdated,
                'execution_time' => microtime(true) - $startTime,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Exception while processing tidal stations', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception while processing tidal stations: ' . $e->getMessage(),
                'stations_processed' => 0,
                'stations_added' => 0,
                'stations_updated' => 0,
                'execution_time' => microtime(true) - $startTime,
            ];
        }
    }
}
