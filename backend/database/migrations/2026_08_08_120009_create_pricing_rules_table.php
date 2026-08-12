<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->nullable()->constrained('routes')->cascadeOnDelete();
            $table->enum('rule_type', ['base', 'weight_slab', 'peak_hour', 'platform_fee', 'tax']);
            $table->unsignedInteger('min_weight_grams')->nullable();
            $table->unsignedInteger('max_weight_grams')->nullable();
            $table->unsignedInteger('amount_paise')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['route_id', 'rule_type', 'is_active', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
