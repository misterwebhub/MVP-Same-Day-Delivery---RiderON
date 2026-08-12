<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('prohibited_items_version_id')->nullable()->constrained('prohibited_items_versions')->nullOnDelete();
            $table->dateTime('accepted_at');
            $table->string('ip_address', 45);
            $table->string('device_info', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_declarations');
    }
};
