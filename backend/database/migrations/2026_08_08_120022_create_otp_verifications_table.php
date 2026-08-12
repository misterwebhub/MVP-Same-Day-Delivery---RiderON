<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('purpose', ['login', 'pickup', 'delivery']);
            $table->string('phone', 15);
            $table->string('otp_hash', 255);
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->dateTime('locked_until')->nullable();
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->dateTime('last_sent_at');
            $table->timestamps();

            $table->index(['order_id', 'purpose']);
            $table->index(['phone', 'purpose', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
