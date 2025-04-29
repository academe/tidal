<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tidal_events', function (Blueprint $table) {
            $table->id();
            $table->string('station_id');
            $table->enum('event_type', ['HighWater', 'LowWater']);
            $table->dateTime('event_datetime');
            $table->double('height')->nullable();
            $table->boolean('is_approximate_time')->default(false);
            $table->boolean('is_approximate_height')->default(false);
            $table->boolean('filtered')->default(false);
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Composite unique key to prevent duplicates
            $table->unique(['station_id', 'event_type', 'event_datetime']);

            // Foreign key to tidal stations
            $table->foreign('station_id')
                  ->references('id')
                  ->on('tidal_stations')
                  ->onDelete('cascade');

            // Index for efficient queries
            $table->index(['station_id', 'event_datetime']);
        });

        // Create table to track last fetch time for stations
        Schema::create('tidal_station_fetches', function (Blueprint $table) {
            $table->string('station_id')->primary();
            $table->dateTime('last_fetch_at')->nullable();
            $table->boolean('fetch_error')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Foreign key to tidal stations
            $table->foreign('station_id')
                  ->references('id')
                  ->on('tidal_stations')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tidal_events');
        Schema::dropIfExists('tidal_station_fetches');
    }
};
