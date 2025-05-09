<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">UK Tidal Stations</h1>
                        <div class="flex space-x-2">
                            <a href="{{ route('tidal.map') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                View Map
                            </a>
                            <a href="{{ route('tidal.admin') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Admin
                            </a>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mb-6">
                        <form action="{{ route('tidal.index') }}" method="GET" class="flex gap-4">
                            <div class="flex-grow">
                                <label for="search" class="sr-only">Search stations</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           placeholder="Search stations...">
                                </div>
                            </div>

                            <div>
                                <label for="country" class="sr-only">Country</label>
                                <select name="country" id="country"
                                        class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Countries</option>
                                    @foreach(App\Models\TidalStation::distinct('country')->whereNotNull('country')->orderBy('country')->pluck('country') as $country)
                                        <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>
                                            {{ $country }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Filter
                            </button>

                            @if(request('search') || request('country'))
                                <a href="{{ route('tidal.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Clear
                                </a>
                            @endif
                        </form>
                    </div>

                    <!-- Stations Table -->
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg mb-6">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Country</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ID</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Coordinates</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($stations as $station)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            {{ $station->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $station->country }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $station->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            @if($station->latitude && $station->longitude)
                                                {{ $station->latitude }}, {{ $station->longitude }}
                                            @else
                                                <span class="text-gray-400">Not available</span>
                                            @endif
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="{{ route('tidal.station', $station->id) }}" class="text-blue-600 hover:text-blue-900">
                                                View<span class="sr-only">, {{ $station->name }}</span>
                                            </a>
                                            <span class="text-gray-300 mx-2">|</span>
                                            <a href="{{ route('tidal.map', ['selectedStationId' => $station->id]) }}" class="text-blue-600 hover:text-blue-900">
                                                Map<span class="sr-only">, {{ $station->name }}</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            No stations found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $stations->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>