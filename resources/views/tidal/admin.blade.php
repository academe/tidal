<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Tidal API Data Administration</h1>

                    <!-- Status Message -->
                    @if (session('status'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <span class="block sm:inline">{{ session('status') }}</span>
                        </div>
                    @endif

                    <!-- Data Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg shadow">
                            <h3 class="font-semibold text-gray-700 mb-2">Total Stations</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ $stationCount }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg shadow">
                            <h3 class="font-semibold text-gray-700 mb-2">Total Events</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ $eventCount }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg shadow">
                            <h3 class="font-semibold text-gray-700 mb-2">Last Fetch</h3>
                            <p class="text-lg text-blue-600">
                                @if($lastFetchDate)
                                    {{ \Carbon\Carbon::parse($lastFetchDate)->diffForHumans() }}
                                    <span class="block text-sm text-gray-500">{{ \Carbon\Carbon::parse($lastFetchDate)->format('Y-m-d H:i:s') }}</span>
                                @else
                                    Never
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Fetch Stations -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Fetch Tidal Stations</h3>
                            <p class="text-gray-600 mb-4">
                                Fetch all available tidal stations from the ADMIRALTY UK Tidal API.
                                This will add new stations and update existing ones.
                            </p>

                            <form action="{{ route('tidal.fetch-stations') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Fetch Stations
                                </button>
                            </form>
                        </div>

                        <!-- Fetch Events -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Fetch Tidal Events</h3>
                            <p class="text-gray-600 mb-4">
                                Fetch tidal events (high and low water) for stations that haven't been updated recently.
                            </p>

                            <form action="{{ route('tidal.fetch-events') }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Size</label>
                                        <input type="number" name="batch_size" value="10" min="1" max="50"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Delay (ms)</label>
                                        <input type="number" name="delay" value="500" min="100" max="5000" step="100"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Days</label>
                                        <select name="duration"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            @for($i = 1; $i <= 7; $i++)
                                                <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'day' : 'days' }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Fetch Events
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Links -->
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('tidal.map') }}" class="py-2 px-4 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                            View Stations Map
                        </a>

                        <a href="{{ route('tidal.index') }}" class="py-2 px-4 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                            View All Stations
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
