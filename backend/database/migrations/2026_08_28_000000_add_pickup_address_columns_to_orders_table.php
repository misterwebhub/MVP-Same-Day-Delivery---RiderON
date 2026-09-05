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
            // Free-text pickup address the customer types in manually, plus
            // the GPS coordinate captured on their device at the moment they
            // typed it (best-effort, browser/OS geolocation — never geocoded
            // from the text). Only ever populated for orders whose route
            // origin station is in a city we've opted into manual pickup for
            // (currently: Kanpur) — everywhere else stays station-only and
            // these columns are null, and the partner app/route origin
            // station's fixed lat/lng is used instead.
            $table->string('pickup_address_text', 500)->nullable()->after('receiver_landmark');
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_address_text');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_address_text', 'pickup_latitude', 'pickup_longitude']);
        });
    }
};
