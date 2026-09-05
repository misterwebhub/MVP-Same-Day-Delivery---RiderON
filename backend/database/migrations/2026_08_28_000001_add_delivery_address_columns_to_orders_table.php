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
            // Mirrors pickup_address_text/pickup_latitude/pickup_longitude
            // (see 2026_08_28_000000_add_pickup_address_columns_to_orders_table)
            // but for the delivery/destination end of the route. Only ever
            // populated for orders whose route destination station is in a
            // city we've opted into manual address entry for (see
            // config('parcel.manual_address_cities'), currently: Kanpur) —
            // everywhere else stays station-only and these columns are null.
            $table->string('delivery_address_text', 500)->nullable()->after('pickup_longitude');
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address_text');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_address_text', 'delivery_latitude', 'delivery_longitude']);
        });
    }
};
