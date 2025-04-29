<?php

namespace App\Console\Commands;

use App\Actions\FetchTidalEventsAction;
use App\Models\TidalStation;
use Illuminate\Console\Command;

class FetchTidalEvents extends Command
{
    protected $signature = 'uk-tidal:fetch-events
                            {--duration=7 : Number of days to fetch (1-7)}
                            {--station=* : Specific station IDs to fetch}
                            {--batch=10 : Number of stations to process in one batch}
                            {--delay=500 : Millisecond delay between API calls}
                            {--force : Force refresh even for recently fetched stations}';

    protected $description = 'Fetch and store UK tidal events from the Admiralty API';

    public function handle()
    {
        $duration = $this->option('duration');
        $stationIds = $this->option('station') ?: [];
        $batchSize = $this->option('batch');
        $delay = $this->option('delay');
        $force = $this->option('force');

        // Check if stations table has data
        $stationCount = app(TidalStation::class)->count();
        if ($stationCount === 0) {
            $this->error('No tidal stations found in the database. Please run the following command first:');
            $this->line('  php artisan uk-tidal:fetch-stations');
            return Command::FAILURE;
        }

        // Validate duration
        if ($duration < 1 || $duration > 7) {
            $this->error('Duration must be between 1 and 7 days.');
            return Command::FAILURE;
        }

        // Create the action with the specified batch size and delay
        $action = app()->makeWith(FetchTidalEventsAction::class, [
            'batchSize' => $batchSize,
            'rateLimitDelay' => $delay,
        ]);

        $this->info('Starting to fetch UK tidal events...');
        $this->info("Batch size: {$batchSize}, Delay: {$delay}ms, Duration: {$duration} days");

        if (!empty($stationIds)) {
            $this->info("Fetching specific stations: " . implode(', ', $stationIds));
        } else {
            $this->info("Fetching oldest stations first (limit: {$batchSize})");
        }

        $result = $action->execute($duration, $stationIds, $force);

        if ($result['success']) {
            $this->info('Successfully fetched tidal events.');
            $this->info("Stations processed: {$result['stations_processed']}");
            $this->info("Stations succeeded: {$result['stations_succeeded']}");
            $this->info("Stations failed: {$result['stations_failed']}");
            $this->info("Events added: {$result['events_added']}");
            $this->info("Execution time: {$result['execution_time']} seconds");

            // Suggest scheduling if it was successful
            if ($result['events_added'] > 0) {
                $this->info("\nConsider adding this command to your schedule for regular updates:");
                $this->info("// In app/Console/Kernel.php");
                $this->info('$schedule->command("uk-tidal:fetch-events")->hourly();');
            }

            return Command::SUCCESS;
        } else {
            $this->error('Failed to fetch tidal events: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}
