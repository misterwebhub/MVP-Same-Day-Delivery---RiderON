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
            // Pincode alongside the free-text pickup/delivery address —
            // captured either from Google Place Details/reverse-geocode
            // (see backend PlacesController) or typed/edited by the customer
            // directly. Same manual-address-city gating as the address text
            // columns it sits next to (see resolveManualAddress() in
            // OrderController) — null everywhere else.
            $table->string('pickup_postal_code', 10)->nullable()->after('pickup_longitude');
            $table->string('delivery_postal_code', 10)->nullable()->after('delivery_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_postal_code', 'delivery_postal_code']);
        });
    }
};
