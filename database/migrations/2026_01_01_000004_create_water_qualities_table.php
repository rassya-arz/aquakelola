<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pond_id')->constrained('ponds')->cascadeOnDelete();
            $table->decimal('ph_level', 4, 2);
            $table->decimal('temperature', 5, 2);
            $table->enum('status', ['dalam_batas', 'di_luar_batas']);
            $table->dateTime('measured_at');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Catatan tindakan korektif (terisi bila status di_luar_batas)
            $table->text('corrective_action')->nullable();
            $table->enum('action_status', ['dalam_proses', 'selesai'])->nullable();
            $table->foreignId('action_pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recheck_at')->nullable();
            $table->dateTime('action_recorded_at')->nullable();
            $table->timestamps();

            $table->index(['pond_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_qualities');
    }
};
