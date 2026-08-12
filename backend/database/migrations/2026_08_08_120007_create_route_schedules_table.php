<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->json('days_of_week');
            $table->unsignedInteger('booking_cutoff_minutes_before')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['route_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_schedules');
    }
};
