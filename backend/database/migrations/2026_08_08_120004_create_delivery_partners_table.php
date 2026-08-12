<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('partner_code', 20)->unique();
            $table->string('photo_url')->nullable();
            $table->enum('vehicle_type', ['train', 'bus', 'bike', 'on_foot'])->default('train');
            $table->string('id_proof_type', 50)->nullable();
            $table->text('id_proof_number_encrypted')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('completed_deliveries_count')->default(0);
            $table->foreignId('current_home_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['verification_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
