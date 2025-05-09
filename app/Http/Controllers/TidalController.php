<?php

namespace App\Http\Controllers;

use App\Models\TidalStation;
use App\Models\TidalEvent;
use App\Services\UKTidalApiService;
use App\Actions\FetchTidalEventsAction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TidalController extends Controller
{
    /**
     * Display all tidal stations
     */
    public function index()
    {
        $stations = TidalStation::orderBy('name')->paginate(20);

        return view('tidal.index', [
            'stations' => $stations,
        ]);
    }

    /**
     * Display detailed information for a specific station
     */
    public function stationDetails($stationId)
    {
        $station = TidalStation::findOrFail($stationId);

        // Get events for the next 7 days
        $events = TidalEvent::where('station_id', $stationId)
            ->where('event_datetime', '>=', Carbon::now())
            ->where('event_datetime', '<=', Carbon::now()->addDays(7))
            ->orderBy('event_datetime')
            ->get();

        // If no events found, try to fetch them on-demand
        if ($events->isEmpty()) {
            $this->fetchEventsForStation($stationId);

            // Try to get events again
            $events = TidalEvent::where('station_id', $stationId)
                ->where('event_datetime', '>=', Carbon::now())
                ->where('event_datetime', '<=', Carbon::now()->addDays(7))
                ->orderBy('event_datetime')
                ->get();
        }

        return view('tidal.station-details', [
            'station' => $station,
            'events' => $events,
        ]);
    }

    /**
     * Display stations on map view - redirects to Livewire component
     */
    public function map(Request $request)
    {
        // This is just a convenience method that redirects to the Livewire component
        // with any query parameters preserved
        return redirect()->route('tidal.map', $request->query());
    }

    /**
     * API endpoint to get stations as GeoJSON
     */
    public function stationsGeoJson(Request $request)
    {
        $query = TidalStation::query();

        // Filter by country if provided
        if ($request->has('country')) {
            $query->where('country', $request->input('country'));
        }

        // Filter by search term if provided
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Only get stations with coordinates
        $query->whereNotNull('longitude')
              ->whereNotNull('latitude');

        // Format as GeoJSON
        $stations = $query->get();

        $features = $stations->map(function ($station) {
            return [
                'type' => 'Feature',
                'id' => $station->id,
                'properties' => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'country' => $station->country,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$station->longitude, $station->latitude],
                ],
            ];
        });

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        return response()->json($geoJson);
    }

    /**
     * API endpoint to get tidal events for a station
     */
    public function stationEvents(Request $request, $stationId)
    {
        $station = TidalStation::findOrFail($stationId);

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->addDays(7);

        $events = TidalEvent::where('station_id', $stationId)
            ->where('event_datetime', '>=', $startDate)
            ->where('event_datetime', '<=', $endDate)
            ->orderBy('event_datetime')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'datetime' => $event->event_datetime->toIso8601String(),
                    'height' => $event->height,
                    'is_approximate_time' => $event->is_approximate_time,
                    'is_approximate_height' => $event->is_approximate_height,
                ];
            });

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'country' => $station->country,
            ],
            'events' => $events,
        ]);
    }

    /**
     * Fetch events for a specific station
     *
     * @param string $stationId
     * @return void
     */
    protected function fetchEventsForStation($stationId)
    {
        $action = app()->make(FetchTidalEventsAction::class);
        $action->execute(7, [$stationId], true);
    }

    /**
     * Admin view to manually trigger data fetches
     */
    public function admin()
    {
        $stationCount = TidalStation::count();
        $eventCount = TidalEvent::count();
        $lastFetchDate = TidalEvent::max('created_at');

        return view('tidal.admin', [
            'stationCount' => $stationCount,
            'eventCount' => $eventCount,
            'lastFetchDate' => $lastFetchDate,
        ]);
    }

    /**
     * Trigger a fetch of tidal stations
     */
    public function fetchStations(Request $request)
    {
        $action = app()->make(\App\Actions\FetchTidalStationsAction::class);
        $result = $action->execute();

        return redirect()->route('tidal.admin')->with('status', 'Stations fetch completed. ' .
            ($result['success'] ? 'Success' : 'Failed') . ': ' .
            "Added {$result['stations_added']}, Updated {$result['stations_updated']}");
    }

    /**
     * Trigger a fetch of tidal events
     */
    public function fetchEvents(Request $request)
    {
        $batchSize = $request->input('batch_size', 10);
        $delay = $request->input('delay', 500);
        $duration = $request->input('duration', 7);

        $action = app()->makeWith(\App\Actions\FetchTidalEventsAction::class, [
            'batchSize' => $batchSize,
            'rateLimitDelay' => $delay,
        ]);

        $result = $action->execute($duration);

        return redirect()->route('tidal.admin')->with('status', 'Events fetch completed. ' .
            ($result['success'] ? 'Success' : 'Failed') . ': ' .
            "Processed {$result['stations_processed']} stations, Added {$result['events_added']} events");
    }
}
