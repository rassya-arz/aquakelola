<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('fish_type', ['Nila', 'Lele']);
            $table->enum('status', ['aktif', 'panen'])->default('aktif');
            // Awal siklus budidaya saat ini; catatan terkait kolam dihitung sejak waktu ini.
            $table->timestamp('cycle_started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ponds');
    }
};
