# UK Tidal API Integration for Laravel

This package provides a simple and robust integration with the UK Admiralty Tidal API for Laravel applications. It allows you to fetch and store tidal station information and tidal events (high and low water) for over 600 UK coastal locations.

## Features

- Fetch and store tidal station information including coordinates and metadata
- Fetch and store tidal events (high/low water times and heights)
- Rate limiting protection to respect API usage constraints
- Historical data storage that builds up over time
- Artisan commands for easy data fetching and maintenance
- Action pattern implementation for flexible usage in controllers, jobs, or commands
- Robust error handling and logging

## Requirements

- PHP 8.1+
- Laravel 12.x
- MySQL 8.0+
- Active ADMIRALTY UK Tidal API subscription (at least Discovery tier)

## Installation

### 1. Install the package files

Copy the model, migration, service, action, and command files to your Laravel project structure.

### 2. Add configuration

Add the following to your `config/services.php` file:

```php
'uk_tidal_api' => [
    'base_url' => env('UK_TIDAL_API_BASE_URL', 'https://admiraltyapi.azure-api.net/uktidalapi'),
    'key' => env('UK_TIDAL_API_KEY'),
],
```

### 3. Add environment variables

Add these to your `.env` file:

```
UK_TIDAL_API_BASE_URL=https://admiraltyapi.azure-api.net/uktidalapi
UK_TIDAL_API_KEY=your-subscription-key-here
```

### 4. Run migrations

```bash
php artisan migrate
```

## Usage

### Fetching Tidal Stations

To fetch and store all available tidal stations:

```bash
php artisan uk-tidal:fetch-stations
```

### Fetching Tidal Events

To fetch and store tidal events (high and low water):

```bash
# Basic usage (fetches the oldest stations first)
php artisan uk-tidal:fetch-events

# Customize batch size and delay to manage API rate limits
php artisan uk-tidal:fetch-events --batch=5 --delay=1000

# Fetch events for specific stations
php artisan uk-tidal:fetch-events --station=0300 --station=0301

# Force refresh even for recently fetched stations
php artisan uk-tidal:fetch-events --force

# List available stations before fetching
php artisan uk-tidal:fetch-events --list-stations
```

### Troubleshooting

If you encounter issues with station IDs:

```bash
# Show diagnostic information
php artisan uk-tidal:fix-ids --show-samples

# Test API connectivity with specific station IDs
php artisan uk-tidal:fix-ids --test-api=0300

# Fix database issues
php artisan uk-tidal:fix-ids --fix-db
```

### Scheduling Regular Updates

Add these to your `app/Console/Kernel.php` in the `schedule` method:

```php
// Fetch tidal stations once a day
$schedule->command('uk-tidal:fetch-stations')->daily();

// Fetch tidal events every hour with a small batch size to respect rate limits
$schedule->command('uk-tidal:fetch-events --batch=5 --delay=1000')->hourly();
```

## Using in Controllers

Example controller method to display events for a station:

```php
namespace App\Http\Controllers;

use App\Models\TidalStation;
use App\Models\TidalEvent;
use Carbon\Carbon;

class TidalController extends Controller
{
    /**
     * Display events for a specific station
     */
    public function stationEvents($stationId)
    {
        $station = TidalStation::findOrFail($stationId);

        // Get events for the station
        $events = TidalEvent::where('station_id', $stationId)
            ->where('event_datetime', '>=', Carbon::now()->startOfDay())
            ->orderBy('event_datetime')
            ->get();

        return view('tidal.station-events', [
            'station' => $station,
            'events' => $events,
        ]);
    }
}
```

## API Details

The integration uses the [ADMIRALTY UK Tidal API](https://admiraltyapi.portal.azure-api.net/), which provides authoritative tidal data for UK coastal waters. The Discovery tier (free) allows access to current plus 6 days of tidal events for 607 tidal stations around the United Kingdom.

## Key Components

- **Models**: `TidalStation`, `TidalEvent`, `TidalStationFetch`
- **Services**: `UKTidalApiService`
- **Actions**: `FetchTidalStationsAction`, `FetchTidalEventsAction`
- **Commands**: `FetchTidalStations`, `FetchTidalEvents`, `FixTidalStationIds`

## Important Notes

- The API expects station IDs as strings (like "0300"). Our models are configured to use string IDs instead of integers.
- For best results, respect API rate limits by using appropriate batch sizes and delays.
- The database is designed to keep a historical record of tidal events over time, which may require periodic database maintenance for very long-term usage.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
