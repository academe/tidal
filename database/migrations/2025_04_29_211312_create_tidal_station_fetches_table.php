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
        // Create table to track last fetch time for stations
        Schema::create('tidal_station_fetches', function (Blueprint $table) {
            $table->id();

            // Foreign key to tidal_stations table
            $table->foreignId('tidal_station_id')
                  ->constrained('tidal_stations')
                  ->onDelete('cascade');

            // $table->string('station_id');
            $table->dateTime('last_fetch_at')->nullable();
            $table->boolean('fetch_error')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Foreign key to tidal stations
            // $table->foreign('station_id')
            //       ->references('id')
            //       ->on('tidal_stations')
            //       ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tidal_station_fetches');
    }
};
