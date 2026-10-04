<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A coupon flagged here waives just the door-pickup fee (not the rest of
     * the order) when the customer has door pickup selected — separate from
     * the existing flat/percentage discount fields, which still apply to the
     * shipment cost as normal.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('waives_door_pickup')->default(false)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('waives_door_pickup');
        });
    }
};
