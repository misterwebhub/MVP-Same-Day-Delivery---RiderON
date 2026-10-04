<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional door-pickup add-on (flat fee, waivable by a dedicated coupon
     * flag — see coupons.waives_door_pickup) selected at booking time.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('door_pickup')->default(false)->after('delivery_postal_code');
            $table->unsignedInteger('door_pickup_fee_paise')->default(0)->after('door_pickup');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['door_pickup', 'door_pickup_fee_paise']);
        });
    }
};
