<?php

/**
 * Artisan Command to Fetch Tidal Stations
 * Path: app/Console/Commands/FetchTidalStations.php
 */

namespace App\Console\Commands;

use App\Actions\FetchTidalStationsAction;
use Illuminate\Console\Command;

class FetchTidalStations extends Command
{
    protected $signature = 'uk-tidal:fetch-stations';
    protected $description = 'Fetch and store UK tidal stations from the Admiralty API';

    public function handle(FetchTidalStationsAction $action)
    {
        $this->info('Starting to fetch UK tidal stations...');

        $result = $action->execute();

        if ($result['success']) {
            $this->info('Successfully fetched tidal stations.');
            $this->info("Processed: {$result['stations_processed']} stations");
            $this->info("Added: {$result['stations_added']} stations");
            $this->info("Updated: {$result['stations_updated']} stations");
            $this->info("Execution time: {$result['execution_time']} seconds");
            return Command::SUCCESS;
        } else {
            $this->error('Failed to fetch tidal stations: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}
