<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prohibited_items_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_label', 20);
            $table->json('content');
            $table->dateTime('published_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prohibited_items_versions');
    }
};
