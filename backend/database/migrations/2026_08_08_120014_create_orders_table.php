<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 20)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->foreignId('route_schedule_id')->constrained('route_schedules')->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('delivery_partners')->nullOnDelete();
            $table->string('status', 40)->default('DRAFT');
            $table->date('booking_date');
            $table->string('sender_name', 150);
            $table->string('sender_phone', 15);
            $table->string('sender_landmark', 255)->nullable();
            $table->string('receiver_name', 150);
            $table->string('receiver_phone', 15);
            $table->string('receiver_landmark', 255)->nullable();
            $table->json('price_breakdown');
            $table->unsignedInteger('total_amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->dateTime('prohibited_items_declared_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->enum('cancelled_by', ['customer', 'partner', 'admin', 'system'])->nullable();
            $table->dateTime('arrived_destination_at')->nullable();
            $table->dateTime('waiting_deadline_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['partner_id', 'status']);
            $table->index(['route_id', 'booking_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
