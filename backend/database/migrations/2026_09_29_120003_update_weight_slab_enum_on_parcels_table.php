<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The parcels.weight_slab column was created as
 * ENUM('upto_1kg','1_3kg','3_5kg','5_10kg') in
 * 2026_08_08_120017_create_parcels_table.php, but the weight-tier pricing
 * replacement feature moved the app to a 3-tier scheme —
 * Parcel::WEIGHT_UPTO_100G / WEIGHT_UPTO_1KG / WEIGHT_UPTO_2KG,
 * config('pricing.weight_slab_grams'), and @rideron/types' WeightSlab —
 * without ever updating this enum. Inserting 'upto_100g' or 'upto_2kg'
 * against the stale enum causes MySQL warning 1265 (data truncated) because
 * neither value is a member of the old enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Widen to a superset enum first so existing old-tier rows
        // ('1_3kg', '3_5kg', '5_10kg') and the new 'upto_100g'/'upto_2kg'
        // values can coexist while we remap the data below — MySQL rejects
        // an UPDATE to a value not yet in the enum.
        DB::statement(
            "ALTER TABLE parcels MODIFY COLUMN weight_slab ENUM('upto_100g', 'upto_1kg', '1_3kg', '3_5kg', '5_10kg', 'upto_2kg') NOT NULL"
        );

        // Existing rows may carry the old 4-tier values ('1_3kg', '3_5kg',
        // '5_10kg') which have no equivalent in the new 3-tier scheme — remap
        // them to the new top tier (upto_2kg). 'upto_1kg' is unchanged and
        // valid in both schemes.
        DB::table('parcels')
            ->whereIn('weight_slab', ['1_3kg', '3_5kg', '5_10kg'])
            ->update(['weight_slab' => 'upto_2kg']);

        // Narrow down to the final 3-tier enum now that no row references a
        // dropped value.
        DB::statement(
            "ALTER TABLE parcels MODIFY COLUMN weight_slab ENUM('upto_100g', 'upto_1kg', 'upto_2kg') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE parcels MODIFY COLUMN weight_slab ENUM('upto_1kg', '1_3kg', '3_5kg', '5_10kg') NOT NULL"
        );
    }
};
