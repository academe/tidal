<?php

namespace App\Livewire;
// namespace App\View\Components;

use App\Models\TidalStation;
use App\Models\TidalEvent;
use Livewire\Component;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class TidalStationsMap extends Component
{
    /**
     * Selected station ID, can be passed in URL
     */
    #[Url]
    public ?string $selectedStationId = null;

    /**
     * Filter by country
     */
    #[Url]
    public ?string $countryFilter = null;

    /**
     * Search term for station names
     */
    #[Url]
    public string $search = '';

    /**
     * Show only stations with recent tidal events
     */
    public bool $showOnlyWithEvents = false;

    /**
     * Lazy load station details when selected
     */
    public ?array $selectedStationDetails = null;

    /**
     * Mount the component
     */
    public function mount()
    {
        // If a station is selected, load its details
        if ($this->selectedStationId) {
            $this->loadStationDetails($this->selectedStationId);
        }
    }

    /**
     * Get all available countries for filtering
     */
    public function getCountriesProperty()
    {
        return TidalStation::distinct('country')
            ->whereNotNull('country')
            ->orderBy('country')
            ->pluck('country');
    }

    /**
     * Load details for a selected station
     */
    public function loadStationDetails(string $stationId)
    {
        $this->selectedStationId = $stationId;

        $station = TidalStation::find($stationId);

        if (!$station) {
            $this->selectedStationDetails = null;
            return;
        }

        // Get upcoming events for the station
        $upcomingEvents = TidalEvent::where('station_id', $stationId)
            ->where('event_datetime', '>=', Carbon::now())
            ->orderBy('event_datetime')
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'datetime' => $event->event_datetime->format('Y-m-d H:i'),
                    'height' => $event->height,
                    'is_approximate' => $event->is_approximate_time || $event->is_approximate_height,
                ];
            });

        $this->selectedStationDetails = [
            'id' => $station->id,
            'name' => $station->name,
            'country' => $station->country,
            'coordinates' => [$station->longitude, $station->latitude],
            'continuous_heights_available' => $station->continuous_heights_available,
            'footnote' => $station->footnote,
            'events' => $upcomingEvents,
        ];
    }

    /**
     * Clear the current station selection
     */
    public function clearSelection()
    {
        $this->selectedStationId = null;
        $this->selectedStationDetails = null;
    }

    /**
     * Set the country filter
     */
    public function setCountryFilter($country)
    {
        $this->countryFilter = $country === $this->countryFilter ? null : $country;
    }

    /**
     * Get the filtered stations for the map
     */
    public function getStationsForMapProperty()
    {
        $query = TidalStation::query();

        // Apply country filter if set
        if ($this->countryFilter) {
            $query->where('country', $this->countryFilter);
        }

        // Apply search filter if set
        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        // Filter stations with events if requested
        if ($this->showOnlyWithEvents) {
            $query->whereHas('tidalEvents', function ($q) {
                $q->where('event_datetime', '>=', Carbon::now());
            });
        }

        // Get stations with coordinates
        $query->whereNotNull('longitude')
              ->whereNotNull('latitude');

        // Return as GeoJSON format
        return $query->get()->map(function ($station) {
            return [
                'type' => 'Feature',
                'id' => $station->id,
                'properties' => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'country' => $station->country,
                    'selected' => $station->id === $this->selectedStationId,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$station->longitude, $station->latitude],
                ],
            ];
        })->toArray();
    }

    /**
     * Get countries as a formatted array for the dropdown
     */
    public function getFormattedCountriesProperty()
    {
        return $this->getCountriesProperty()->map(function ($country) {
            return [
                'name' => $country,
                'active' => $country === $this->countryFilter,
            ];
        });
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.tidal-stations-map');
    }
}
