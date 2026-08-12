<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('otp_verification_id')->constrained('otp_verifications')->cascadeOnDelete();
            $table->enum('attempted_by_type', ['customer', 'partner']);
            $table->unsignedBigInteger('attempted_by_id');
            $table->enum('result', ['success', 'invalid', 'expired', 'locked']);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verification_logs');
    }
};
