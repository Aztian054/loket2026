<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Loket Pertanahan Elektronik BMN BALAM
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Tracking & QR Validation
    Route::get('/tracking/{no_tiket}', [ApiController::class, 'tracking']);
    
    // Live Dashboard Stats
    Route::get('/stats', [ApiController::class, 'stats']);
    
    // Master Services
    Route::get('/jenis-permohonan', [ApiController::class, 'jenisPermohonan']);
});
