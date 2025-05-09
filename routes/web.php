<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use App\Livewire\TidalStationsMap;
// use App\View\Components\TidalStationsMap;
use App\Http\Controllers\TidalController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

Route::get('/tidal/map', TidalStationsMap::class)->name('tidal.map');
Route::get('/tidal/station/{stationId}', [TidalController::class, 'stationDetails'])->name('tidal.station');

// Tidal API Group
Route::prefix('tidal')->name('tidal.')->group(function () {
    // Main views
    Route::get('/', [TidalController::class, 'index'])->name('index');
    Route::get('/station/{stationId}', [TidalController::class, 'stationDetails'])->name('station');
    Route::get('/map', TidalStationsMap::class)->name('map');

    // Alternative map route through controller (for convenience)
    Route::get('/view-map', [TidalController::class, 'map'])->name('view-map');

    // Admin routes (you might want to add middleware for authentication)
    Route::get('/admin', [TidalController::class, 'admin'])->name('admin')->middleware(['auth']);
    Route::post('/admin/fetch-stations', [TidalController::class, 'fetchStations'])->name('fetch-stations')->middleware(['auth']);
    Route::post('/admin/fetch-events', [TidalController::class, 'fetchEvents'])->name('fetch-events')->middleware(['auth']);

    // API endpoints (JSON responses)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stations', [TidalController::class, 'stationsGeoJson'])->name('stations');
        Route::get('/station/{stationId}/events', [TidalController::class, 'stationEvents'])->name('station-events');
    });
});

require __DIR__.'/auth.php';
