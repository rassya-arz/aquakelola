<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FeedStockController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\WaterQualityController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [AuthController::class, 'redirectByRole'])->name('home');

    // Pengelola & Mitra/Pemilik: halaman laporan (hanya baca)
    Route::middleware('role:pengelola,mitra_pemilik')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/laba-rugi', [FinancialController::class, 'index'])->name('finance.index');
        Route::get('/laba-rugi/ekspor', [FinancialController::class, 'export'])->name('finance.export');
    });

    // Pengelola saja: operasional & pencatatan data
    Route::middleware('role:pengelola')->group(function () {
        Route::get('/stok-pakan', [FeedController::class, 'index'])->name('feed.index');
        Route::post('/stok-pakan/transaksi', [FeedController::class, 'storeTransaction'])->name('feed.store');
        Route::get('/stok-pakan/transaksi/{feedTransaction}/edit', [FeedController::class, 'editTransaction'])->name('feed.edit');
        Route::put('/stok-pakan/transaksi/{feedTransaction}', [FeedController::class, 'updateTransaction'])->name('feed.update');
        Route::delete('/stok-pakan/transaksi/{feedTransaction}', [FeedController::class, 'destroyTransaction'])->name('feed.destroy');

        Route::get('/pengecekan-ph', [WaterQualityController::class, 'index'])->name('water.index');
        Route::post('/pengecekan-ph', [WaterQualityController::class, 'store'])->name('water.store');
        Route::patch('/pengecekan-ph/{waterQuality}/tindakan', [WaterQualityController::class, 'updateAction'])->name('water.action');
        Route::get('/pengecekan-ph/{waterQuality}/edit', [WaterQualityController::class, 'edit'])->name('water.edit');
        Route::put('/pengecekan-ph/{waterQuality}', [WaterQualityController::class, 'update'])->name('water.update');
        Route::delete('/pengecekan-ph/{waterQuality}', [WaterQualityController::class, 'destroy'])->name('water.destroy');

        Route::post('/laba-rugi', [FinancialController::class, 'store'])->name('finance.store');
        Route::get('/laba-rugi/{financialTransaction}/edit', [FinancialController::class, 'edit'])->name('finance.edit');
        Route::put('/laba-rugi/{financialTransaction}', [FinancialController::class, 'update'])->name('finance.update');
        Route::delete('/laba-rugi/{financialTransaction}', [FinancialController::class, 'destroy'])->name('finance.destroy');

        // Master data
        Route::resource('kolam', PondController::class)->except(['show', 'create'])->parameters(['kolam' => 'pond'])->names('ponds');
        Route::resource('jenis-pakan', FeedStockController::class)->except(['show', 'create'])->parameters(['jenis-pakan' => 'feedStock'])->names('feedstocks');
        Route::resource('pengguna', UserController::class)->except(['show', 'create'])->parameters(['pengguna' => 'user'])->names('users');
        
        Route::get('/pengecekan-ph/cuaca', [\App\Http\Controllers\WeatherController::class, 'current'])->name('water.weather');
    });
    
});
