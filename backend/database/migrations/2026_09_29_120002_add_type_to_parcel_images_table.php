<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguishes the fraud-prevention "what's actually being shipped"
     * photo (existing, default) from a bill/invoice image the customer
     * attaches when declared_value_paise is high enough to need one for an
     * insurance claim (see Parcel::INVOICE_REQUIRED_ABOVE_PAISE).
     */
    public function up(): void
    {
        Schema::table('parcel_images', function (Blueprint $table) {
            $table->enum('type', ['photo', 'invoice'])->default('photo')->after('storage_path');
        });
    }

    public function down(): void
    {
        Schema::table('parcel_images', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
