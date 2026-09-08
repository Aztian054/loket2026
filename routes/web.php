<?php

use App\Http\Controllers\AlihMediaController;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoketController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ValidatorController;
use App\Http\Controllers\VerifikatorController;
use App\Http\Controllers\WarkahController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Loket Pertanahan Elektronik BMN BALAM
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or tracking
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('tracking.index');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Tracking (Bisa diakses siapapun tanpa login via scan QR atau input no tiket)
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
// no_tiket memakai slash (mis. K/9/141024/1), jadi perlu where '.*' agar cocok di URL
Route::get('/tracking/{no_tiket}', [TrackingController::class, 'show'])
    ->where('no_tiket', '.*')
    ->name('tracking.show');

// Protected Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard (Semua role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 0. Notifikasi & Konfirmasi Revisi (Semua role)
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'tandaiSemuaBaca'])->name('notifikasi.bacaSemua');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'tandaiBaca'])->name('notifikasi.tandaiBaca');

    // 1. Modul Loket
    Route::middleware(['role:loket'])->prefix('loket')->name('loket.')->group(function () {
        Route::get('/', [LoketController::class, 'index'])->name('index');
        Route::get('/create', [LoketController::class, 'create'])->name('create');
        Route::post('/', [LoketController::class, 'store'])->name('store');
        Route::get('/{id}', [LoketController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [LoketController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LoketController::class, 'update'])->name('update');
        Route::post('/{id}/forward', [LoketController::class, 'forwardToVerifikator'])->name('forward');
        Route::post('/{id}/resubmit', [LoketController::class, 'resubmit'])->name('resubmit');
        Route::post('/{id}/konfirmasi-revisi/{revisiId}', [LoketController::class, 'konfirmasiRevisi'])->name('konfirmasiRevisi');
        Route::get('/{id}/print-receipt', [LoketController::class, 'printReceipt'])->name('printReceipt');
        Route::get('/{id}/print-checklist', [LoketController::class, 'printChecklist'])->name('printChecklist');
    });

    // 2. Modul Verifikator
    Route::middleware(['role:verifikator'])->prefix('verifikator')->name('verifikator.')->group(function () {
        Route::get('/', [VerifikatorController::class, 'index'])->name('index');
        Route::get('/{id}', [VerifikatorController::class, 'show'])->name('show');
        Route::post('/{id}/update', [VerifikatorController::class, 'updateVerification'])->name('update');
        Route::post('/{id}/konfirmasi-revisi/{revisiId}', [VerifikatorController::class, 'konfirmasiRevisi'])->name('konfirmasiRevisi');
        Route::get('/{id}/print-saran-koreksi', [VerifikatorController::class, 'printSaranKoreksi'])->name('printSaranKoreksi');
    });

    // 3. Modul Warkah
    Route::middleware(['role:warkah'])->prefix('warkah')->name('warkah.')->group(function () {
        Route::get('/', [WarkahController::class, 'index'])->name('index');
        Route::get('/{id}', [WarkahController::class, 'show'])->name('show');
        Route::post('/{id}/update', [WarkahController::class, 'updateWarkah'])->name('update');
        Route::post('/{id}/konfirmasi-revisi/{revisiId}', [WarkahController::class, 'konfirmasiRevisi'])->name('konfirmasiRevisi');
    });

    // 4. Modul Validator
    Route::middleware(['role:validator'])->prefix('validator')->name('validator.')->group(function () {
        Route::get('/', [ValidatorController::class, 'index'])->name('index');
        Route::get('/{id}', [ValidatorController::class, 'show'])->name('show');
        Route::post('/{id}/update', [ValidatorController::class, 'updateValidasi'])->name('update');
        Route::post('/{id}/konfirmasi-revisi/{revisiId}', [ValidatorController::class, 'konfirmasiRevisi'])->name('konfirmasiRevisi');
    });

    // 5. Modul Alih Media
    Route::middleware(['role:alih_media'])->prefix('alih-media')->name('alih_media.')->group(function () {
        Route::get('/', [AlihMediaController::class, 'index'])->name('index');
        Route::get('/{id}', [AlihMediaController::class, 'show'])->name('show');
        Route::post('/{id}/update', [AlihMediaController::class, 'updateAlihMedia'])->name('update');
        Route::post('/{id}/konfirmasi-revisi/{revisiId}', [AlihMediaController::class, 'konfirmasiRevisi'])->name('konfirmasiRevisi');
    });

    // 6. Modul Pembayaran (Grand Final)
    Route::middleware(['role:pembayaran'])->prefix('pembayaran')->name('pembayaran.')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])->name('index');
        Route::get('/{id}', [PembayaranController::class, 'show'])->name('show');
        Route::post('/{id}/update', [PembayaranController::class, 'updatePembayaran'])->name('update');
    });

    // 7. Laporan & Monitoring (Pimpinan, Admin, Staff)
    Route::middleware(['role:pimpinan,loket,verifikator,warkah,validator,alih_media,pembayaran'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/print', [ReportController::class, 'print'])->name('print');
    });

    // 8. Arsip Tahunan (Admin menulis, Pimpinan read-only)
    Route::middleware(['role:admin,pimpinan'])->prefix('arsip')->name('arsip.')->group(function () {
        Route::get('/', [ArsipController::class, 'index'])->name('index');
        Route::get('/{id}', [ArsipController::class, 'show'])->name('show');
        Route::post('/{id}/arsipkan', [ArsipController::class, 'arsipkan'])->name('arsipkan');
        Route::post('/arsipkan-massal', [ArsipController::class, 'arsipkanMassal'])->name('arsipkanMassal');
        Route::post('/arsipkan-tahun/{tahun}', [ArsipController::class, 'arsipkanMassalTahun'])->name('arsipkanTahun');
        Route::post('/{id}/restore', [ArsipController::class, 'restore'])->name('restore');
    });

});
