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
        Schema::table('orders', function (Blueprint $table) {
            // Rider-captured proof-of-custody photos — fraud-prevention evidence that the
            // rider actually had the physical parcel at pickup / handed it over at delivery,
            // independent of (and in addition to) the OTP.
            $table->string('pickup_proof_photo_path', 255)->nullable()->after('waiting_deadline_at');
            $table->string('delivery_proof_photo_path', 255)->nullable()->after('pickup_proof_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_proof_photo_path', 'delivery_proof_photo_path']);
        });
    }
};
