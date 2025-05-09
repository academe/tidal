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

            $table->foreignId('tidal_station_id')
                  ->constrained('tidal_stations')
                  ->onDelete('cascade');

            // @todo just use a string here, and cast to an enum in the model.
            $table->enum('event_type', ['HighWater', 'LowWater']);
            $table->dateTime('event_datetime');
            $table->double('height')->nullable();
            $table->boolean('is_approximate_time')->default(false);
            $table->boolean('is_approximate_height')->default(false);
            $table->boolean('filtered')->default(false);
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Composite unique key to prevent duplicates
            $table->unique(['tidal_station_id', 'event_type', 'event_datetime']);

            // Foreign key to tidal stations
            // $table->foreign('station_id')
            //       ->references('id')
            //       ->on('tidal_stations')
            //       ->onDelete('cascade');

            // Index for efficient queries
            $table->index(['tidal_station_id', 'event_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tidal_events');
    }
};
