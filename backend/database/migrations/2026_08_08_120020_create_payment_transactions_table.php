<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('provider_payment_id', 100)->nullable();
            $table->string('provider_signature', 255)->nullable();
            $table->string('event_type', 50);
            $table->json('raw_payload');
            $table->boolean('signature_verified')->default(false);
            $table->boolean('processed')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['provider_payment_id', 'event_type']);
            $table->index(['payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
