<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tidal_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_id')->unique()->comment('The ID of the station from the API');
            $table->string('name');
            $table->string('country')->nullable();
            $table->double('longitude')->nullable()->comment('WGS84 longitude degrees');
            $table->double('latitude')->nullable()->comment('WGS84 latitude degrees');
            $table->boolean('continuous_heights_available')->default(false);
            $table->text('footnote')->nullable();
            $table->json('raw_data')->nullable()->comment('The full JSON response');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tidal_stations');
    }
};