<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_schedule_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_schedule_id')->constrained('route_schedules')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_cancelled')->default(false);
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['route_schedule_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_schedule_dates');
    }
};
