<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_transactions', function (Blueprint $table) {
            $table->id();
            // pond_id nullable: pakan masuk (pembelian) tidak terkait kolam tertentu
            $table->foreignId('pond_id')->nullable()->constrained('ponds')->nullOnDelete();
            $table->foreignId('feed_id')->constrained('feed_stocks')->cascadeOnDelete();
            $table->enum('type', ['masuk', 'keluar']);
            $table->decimal('quantity', 10, 2);
            $table->string('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['feed_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_transactions');
    }
};
