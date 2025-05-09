<div class="relative w-full h-screen flex flex-col">
    <!-- Filter Bar -->
    <div class="bg-white p-4 shadow-md z-10 flex flex-wrap items-center gap-4">
        <div class="flex-grow">
            <label for="search" class="sr-only">Search stations</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search stations...">
            </div>
        </div>

        <div class="flex items-center">
            <span class="mr-2 text-sm text-gray-700">Country:</span>
            <div class="relative">
                <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="country-menu-button" aria-expanded="true" aria-haspopup="true" x-data="{}" x-on:click="$refs.countryDropdown.classList.toggle('hidden')">
                    {{ $countryFilter ?? 'All Countries' }}
                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-ref="countryDropdown" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-20" role="menu" aria-orientation="vertical" aria-labelledby="country-menu-button" tabindex="-1">
                    <div class="py-1" role="none">
                        <a wire:click="setCountryFilter(null)" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100 cursor-pointer {{ !$countryFilter ? 'bg-gray-100 font-semibold' : '' }}">All Countries</a>
                    </div>
                    <div class="py-1 max-h-60 overflow-y-auto" role="none">
                        @foreach($this->formattedCountries as $country)
                            <a wire:click="setCountryFilter('{{ $country['name'] }}')" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100 cursor-pointer {{ $country['active'] ? 'bg-gray-100 font-semibold' : '' }}">
                                {{ $country['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center">
            <label for="show-with-events" class="flex items-center cursor-pointer">
                <div class="relative">
                    <input type="checkbox" id="show-with-events" wire:model.live="showOnlyWithEvents" class="sr-only">
                    <div class="block bg-gray-300 w-14 h-8 rounded-full"></div>
                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                </div>
                <div class="ml-3 text-sm text-gray-700">Show only with events</div>
            </label>
        </div>
    </div>

    <div class="flex-grow flex">
        <!-- Map Container -->
        <div id="map-container" class="w-full h-full" wire:ignore>
            <!-- Leaflet map will be initialized here -->
        </div>

        <!-- Station Details Sidebar (conditionally shown) -->
        @if($selectedStationDetails)
            <div class="absolute top-0 right-0 h-full w-96 bg-white shadow-lg z-10 p-4 overflow-y-auto transform transition-transform">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">{{ $selectedStationDetails['name'] }}</h2>
                    <button wire:click="clearSelection" class="p-2 rounded-full hover:bg-gray-200">
                        <svg class="h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-6">
                    <div class="text-sm text-gray-500">Station ID: {{ $selectedStationDetails['id'] }}</div>
                    <div class="text-sm text-gray-500">Country: {{ $selectedStationDetails['country'] }}</div>
                    <div class="text-sm text-gray-500">Coordinates: {{ $selectedStationDetails['coordinates'][1] }}, {{ $selectedStationDetails['coordinates'][0] }}</div>
                    <div class="text-sm text-gray-500">
                        Continuous Heights: {{ $selectedStationDetails['continuous_heights_available'] ? 'Available' : 'Not Available' }}
                    </div>

                    @if($selectedStationDetails['footnote'])
                        <div class="mt-2 p-2 bg-gray-100 rounded text-sm">
                            <strong>Note:</strong> {{ $selectedStationDetails['footnote'] }}
                        </div>
                    @endif
                </div>

                <h3 class="text-lg font-semibold mb-2">Upcoming Tidal Events</h3>

                @if(count($selectedStationDetails['events']) > 0)
                    <div class="space-y-3">
                        @foreach($selectedStationDetails['events'] as $event)
                            <div class="p-3 bg-{{ $event['type'] === 'HighWater' ? 'blue' : 'green' }}-50 rounded-lg border border-{{ $event['type'] === 'HighWater' ? 'blue' : 'green' }}-200">
                                <div class="flex justify-between">
                                    <div class="font-medium">{{ $event['type'] === 'HighWater' ? 'High Water' : 'Low Water' }}</div>
                                    <div class="text-sm {{ $event['is_approximate'] ? 'text-amber-600' : 'text-gray-600' }}">
                                        {{ $event['is_approximate'] ? '~' : '' }}{{ $event['height'] ?? '-' }}m
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($event['datetime'])->format('D, j M Y, H:i') }}
                                    @if($event['is_approximate'])
                                        <span class="text-xs text-amber-600 ml-1">(approximate)</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 bg-gray-100 rounded-lg text-center text-gray-600">
                        No upcoming events found
                    </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('tidal.station', $selectedStationDetails['id']) }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700">
                        View Full Schedule
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Load Leaflet.js only once with a script tag in the layout or in this view -->
@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function () {
        // Initialize the map
        const map = L.map('map-container').setView([55.378051, -3.435973], 6); // Center on UK

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Initialize marker layer group
        const markers = L.layerGroup().addTo(map);

        // Function to update markers on the map
        function updateMarkers(stations) {
            // Clear existing markers
            markers.clearLayers();

            // Add markers for each station
            stations.forEach(station => {
                const isSelected = station.properties.selected;

                // Create marker with custom icon
                const marker = L.circleMarker(
                    [station.geometry.coordinates[1], station.geometry.coordinates[0]], {
                        radius: isSelected ? 8 : 6,
                        fillColor: isSelected ? '#2563EB' : '#60A5FA',
                        color: isSelected ? '#1E40AF' : '#3B82F6',
                        weight: isSelected ? 2 : 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    }
                );

                // Add popup with station info
                marker.bindPopup(`
                    <div class="text-center">
                        <div class="font-bold">${station.properties.name}</div>
                        <div class="text-sm text-gray-700">${station.properties.country}</div>
                        <button
                            class="mt-2 py-1 px-3 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
                            onclick="Livewire.dispatch('selectStation', { stationId: '${station.properties.id}' })"
                        >
                            View Details
                        </button>
                    </div>
                `);

                // Add click handler
                marker.on('click', function() {
                    @this.loadStationDetails(station.properties.id);
                });

                // Add to marker layer group
                markers.addLayer(marker);

                // If selected, open popup
                if (isSelected) {
                    marker.openPopup();
                }
            });
        }

        // Update markers when stations data changes
        Livewire.on('stationsUpdated', (event) => {
            updateMarkers(event.stations);
        });

        // Add listeners for marker click from sidebar selection
        Livewire.on('selectStation', (data) => {
            @this.loadStationDetails(data.stationId);
        });

        // Initial load of markers
        updateMarkers(@this.stationsForMap);

        // Update markers when the component is updated
        Livewire.hook('morph.updated', ({ el, component }) => {
            if (component.id === @this.__instance.id) {
                updateMarkers(@this.stationsForMap);
            }
        });
    });
</script>
<style>
    /* Toggle switch styling */
    input:checked ~ .dot {
        transform: translateX(100%);
    }
    input:checked ~ .block {
        background-color: #3B82F6;
    }
</style>
@endpush
