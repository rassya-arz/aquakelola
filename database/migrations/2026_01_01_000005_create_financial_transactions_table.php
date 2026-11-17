<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            // pond_id terisi untuk pendapatan panen; feed_id untuk pembelian pakan
            $table->foreignId('pond_id')->nullable()->constrained('ponds')->restrictOnDelete();
            $table->foreignId('feed_id')->nullable()->constrained('feed_stocks')->restrictOnDelete();
            // pembelian otomatis dari pakan masuk; ikut terhapus bila transaksi pakan dihapus
            $table->foreignId('feed_transaction_id')->nullable()->constrained('feed_transactions')->cascadeOnDelete();
            $table->enum('type', ['pendapatan_panen', 'pembelian_pakan']);
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('price_per_kg', 12, 2);
            $table->decimal('total_amount', 14, 2);
            $table->date('transaction_date');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
