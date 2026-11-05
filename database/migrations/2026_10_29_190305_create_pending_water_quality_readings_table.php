<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_water_quality_readings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->cascadeOnDelete();

            $table->decimal('ph_level', 4, 2);
            $table->decimal('temperature', 5, 2);
            $table->dateTime('measured_at');
            $table->dateTime('expires_at');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', [
                'menunggu',
                'membaik',
                'dicatat',
            ])->default('menunggu');

            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_water_quality_readings');
    }
};