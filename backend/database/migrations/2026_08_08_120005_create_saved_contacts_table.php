<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['sender', 'receiver']);
            $table->string('label', 50)->nullable();
            $table->string('name', 150);
            $table->string('phone', 15);
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('landmark', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_contacts');
    }
};
