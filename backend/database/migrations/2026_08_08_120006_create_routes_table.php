<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('destination_station_id')->constrained('stations')->restrictOnDelete();
            $table->decimal('distance_km', 6, 2);
            $table->unsignedInteger('estimated_duration_minutes');
            $table->time('cutoff_time');
            $table->unsignedInteger('max_parcels_per_schedule')->default(50);
            $table->unsignedInteger('waiting_time_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['origin_station_id', 'destination_station_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
