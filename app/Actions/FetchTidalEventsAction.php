<?php

namespace App\Actions;

use App\Models\TidalEvent;
use App\Models\TidalStation;
use App\Models\TidalStationFetch;
use App\Services\UKTidalApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchTidalEventsAction
{
    protected UKTidalApiService $tidalApiService;
    protected int $batchSize;
    protected int $rateLimitDelay;

    /**
     * @param UKTidalApiService $tidalApiService
     * @param int $batchSize Number of stations to process in one batch
     * @param int $rateLimitDelay Milliseconds to wait between API calls
     */
    public function __construct(
        UKTidalApiService $tidalApiService,
        int $batchSize = 10,
        int $rateLimitDelay = 500
    ) {
        $this->tidalApiService = $tidalApiService;
        $this->batchSize = $batchSize;
        $this->rateLimitDelay = $rateLimitDelay;
    }

    /**
     * Execute the action to fetch and store tidal events
     *
     * @param int $duration Number of days to request (1-7)
     * @param array $specificStationIds Optional array of station IDs to fetch; if empty, uses selection logic
     * @param bool $forceRefresh If true, forces refresh even for recently fetched stations
     * @return array Summary of the operation
     */
    public function execute(
        int $duration = 7,
        array $specificStationIds = [],
        bool $forceRefresh = false
    ): array {
        $startTime = microtime(true);
        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;
        $eventsAdded = 0;

        try {
            // If specific station IDs are provided, validate them first
            if (!empty($specificStationIds)) {
                // Make sure they exist in our database
                $existingStations = TidalStation::whereIn('id', $specificStationIds)->pluck('id')->toArray();
                $missingStations = array_diff($specificStationIds, $existingStations);

                if (!empty($missingStations)) {
                    Log::warning('Some specified stations do not exist in the database', [
                        'missing_stations' => $missingStations
                    ]);

                    // Only use existing stations
                    $specificStationIds = $existingStations;

                    if (empty($specificStationIds)) {
                        return [
                            'success' => false,
                            'message' => 'None of the specified station IDs exist in the database',
                            'stations_processed' => 0,
                            'stations_succeeded' => 0,
                            'stations_failed' => 0,
                            'events_added' => 0,
                            'execution_time' => microtime(true) - $startTime,
                        ];
                    }
                }
            }

            // Get stations to process
            $stations = $this->getStationsToProcess($specificStationIds, $forceRefresh);

            if ($stations->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No stations to process at this time',
                    'stations_processed' => 0,
                    'stations_succeeded' => 0,
                    'stations_failed' => 0,
                    'events_added' => 0,
                    'execution_time' => microtime(true) - $startTime,
                ];
            }

            // Process each station
            foreach ($stations as $station) {
                $processedCount++;

                try {
                    // Respect rate limiting
                    if ($processedCount > 1) {
                        usleep($this->rateLimitDelay * 1000); // Convert to microseconds
                    }

                    // Fetch events for this station
                    $events = $this->tidalApiService->getTidalEvents($station->id, $duration);

                    if (!$events || !is_array($events)) {
                        $this->updateFetchRecord($station->id, true, 'API returned invalid data');
                        $errorCount++;
                        continue;
                    }

                    // Process and save the events
                    $addedCount = $this->processStationEvents($station->id, $events);
                    $eventsAdded += $addedCount;

                    // Update the fetch record
                    $this->updateFetchRecord($station->id, false);
                    $successCount++;

                } catch (\Exception $e) {
                    Log::error("Error fetching events for station {$station->id}", [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $this->updateFetchRecord($station->id, true, $e->getMessage());
                    $errorCount++;
                }

                // If we've hit our batch size, stop
                if ($processedCount >= $this->batchSize) {
                    break;
                }
            }

            return [
                'success' => true,
                'stations_processed' => $processedCount,
                'stations_succeeded' => $successCount,
                'stations_failed' => $errorCount,
                'events_added' => $eventsAdded,
                'execution_time' => microtime(true) - $startTime,
            ];

        } catch (\Exception $e) {
            Log::error('Exception in FetchTidalEventsAction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception in FetchTidalEventsAction: ' . $e->getMessage(),
                'stations_processed' => $processedCount,
                'stations_succeeded' => $successCount,
                'stations_failed' => $errorCount,
                'events_added' => $eventsAdded,
                'execution_time' => microtime(true) - $startTime,
            ];
        }
    }

    /**
     * Get the stations to process based on priority and last fetch time
     *
     * @param array $specificStationIds Specific station IDs to fetch
     * @param bool $forceRefresh If true, ignores the last fetch time
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getStationsToProcess(array $specificStationIds = [], bool $forceRefresh = false)
    {
        // If specific station IDs provided, use those
        if (!empty($specificStationIds)) {
            return TidalStation::whereIn('id', $specificStationIds)->get();
        }

        // Validate that we have stations in the database
        $stationCount = TidalStation::count();
        if ($stationCount === 0) {
            Log::warning('No tidal stations found in database. Run uk-tidal:fetch-stations first.');
            return collect();
        }

        // Create initial query
        $query = TidalStation::query();

        // Join with fetch records to get last fetch time
        $query->leftJoin('tidal_station_fetches', 'tidal_stations.id', '=', 'tidal_station_fetches.station_id');

        // If not forcing refresh, prioritize stations that haven't been fetched recently
        if (!$forceRefresh) {
            $query->where(function ($q) {
                // Never fetched stations (NULL last_fetch_at)
                $q->whereNull('tidal_station_fetches.last_fetch_at')
                  // Or stations with errors
                  ->orWhere('tidal_station_fetches.fetch_error', true)
                  // Or stations not fetched in the last 24 hours
                  ->orWhere('tidal_station_fetches.last_fetch_at', '<', Carbon::now()->subHours(24));
            });
        }

        // Order by fetch time (oldest first) and then by station ID for consistency
        // Explicitly select the station id to ensure we're getting the correct field
        return $query->orderBy('tidal_station_fetches.last_fetch_at', 'asc')
                    ->orderBy('tidal_stations.id', 'asc')
                    ->select('tidal_stations.*')
                    ->limit($this->batchSize)
                    ->get();
    }

    /**
     * Process and save events for a station
     *
     * @param string $stationId
     * @param array $events
     * @return int Number of events added
     */
    protected function processStationEvents(string $stationId, array $events): int
    {
        $addedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($events as $event) {
                // Validate the event data
                if (!isset($event['EventType'], $event['DateTime'])) {
                    continue;
                }

                // Create the event record
                $eventData = [
                    'station_id' => $stationId,
                    'event_type' => $event['EventType'],
                    'event_datetime' => Carbon::parse($event['DateTime']),
                    'height' => $event['Height'] ?? null,
                    'is_approximate_time' => $event['IsApproximateTime'] ?? false,
                    'is_approximate_height' => $event['IsApproximateHeight'] ?? false,
                    'filtered' => $event['Filtered'] ?? false,
                    'raw_data' => $event,
                ];

                // Use updateOrCreate to handle duplicates based on composite key
                $tidalEvent = TidalEvent::updateOrCreate(
                    [
                        'station_id' => $stationId,
                        'event_type' => $event['EventType'],
                        'event_datetime' => Carbon::parse($event['DateTime']),
                    ],
                    $eventData
                );

                if ($tidalEvent->wasRecentlyCreated) {
                    $addedCount++;
                }
            }

            DB::commit();
            return $addedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing events for station {$stationId}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update or create a fetch record for a station
     *
     * @param string $stationId
     * @param bool $hasError
     * @param string|null $errorMessage
     */
    protected function updateFetchRecord(string $stationId, bool $hasError = false, ?string $errorMessage = null): void
    {
        try {
            // First check if the station exists
            $stationExists = TidalStation::where('id', $stationId)->exists();

            if (!$stationExists) {
                Log::error("Cannot update fetch record: Station {$stationId} does not exist in the database");
                return;
            }

            TidalStationFetch::updateOrCreate(
                ['station_id' => $stationId],
                [
                    'last_fetch_at' => Carbon::now(),
                    'fetch_error' => $hasError,
                    'error_message' => $errorMessage ? substr($errorMessage, 0, 255) : null, // Ensure error message isn't too long
                ]
            );
        } catch (\Exception $e) {
            Log::error("Error updating fetch record for station {$stationId}", [
                'message' => $e->getMessage()
            ]);
        }
    }
}
