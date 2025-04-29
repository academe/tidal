<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tidal_stations', function (Blueprint $table) {
            $table->string('id')->primary(); // Station ID from API
            $table->string('name');
            $table->string('country')->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->boolean('continuous_heights_available')->default(false);
            $table->text('footnote')->nullable();
            $table->json('raw_data')->nullable(); // Store the full JSON response
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tidal_stations');
    }
};