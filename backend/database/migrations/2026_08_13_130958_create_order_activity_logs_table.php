<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // e.g. order_booked, payment_verified, partner_matched, partner_accepted,
            // rider_arrived_pickup, pickup_photo_uploaded, pickup_otp_verified,
            // pickup_otp_regenerated, rider_arrived_destination, delivery_photo_uploaded,
            // delivery_otp_verified, delivery_otp_regenerated, order_cancelled.
            $table->string('event', 64);
            // customer | partner | admin | system — who/what triggered this row.
            $table->string('actor_type', 16);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Straight-line distance (metres) between the reported GPS and the
            // relevant station, only computed for arrival-type events — the
            // single cheapest fraud signal this table exists to produce.
            $table->unsignedInteger('distance_from_target_meters')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_activity_logs');
    }
};
