<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('feed_type'); // Pelet Grower / Pelet Starter
            $table->decimal('total_stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_stocks');
    }
};
