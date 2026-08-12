<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('provider', ['razorpay', 'mock']);
            $table->string('provider_order_id', 100)->nullable();
            $table->unsignedInteger('amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['created', 'pending', 'success', 'failed', 'cancelled'])->default('created');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
