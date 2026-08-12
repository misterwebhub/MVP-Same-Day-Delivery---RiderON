<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->enum('parcel_type', ['documents', 'clothing', 'electronics', 'gifts', 'books', 'other']);
            $table->enum('weight_slab', ['upto_1kg', '1_3kg', '3_5kg', '5_10kg']);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('declared_value_paise')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
