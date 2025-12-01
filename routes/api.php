<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TugasController;
use App\Http\Controllers\BotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::get('/register-options', [AuthController::class, 'registerOptions']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Bot routes (untuk webhook dari bot external)
Route::prefix('bot')->group(function () {
    // Endpoint dengan API Key protection (untuk Node.js bot)
    Route::middleware(['bot.key'])->group(function () {
        // Ambil semua siswa yang perlu reminder (optimized untuk bot)
        Route::get('/siswa-pending', [BotController::class, 'ambilSiswaPerluReminder']);
        
        // Ambil siswa pending untuk tugas tertentu
        Route::get('/siswa-pending/{idTugas}', [BotController::class, 'ambilSiswaPendingByTugas']);
        
        // Catat reminder setelah bot berhasil kirim
        Route::post('/reminder', [BotController::class, 'catatReminder']);
        
        // Webhook untuk update status pengiriman (opsional)
        Route::post('/webhook/status', [BotController::class, 'webhookStatus']);
    });
});

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Tugas routes
    Route::prefix('tugas')->group(function () {
        // Guru only
        Route::middleware(['role:guru'])->group(function () {
            Route::post('/', [TugasController::class, 'buatTugas']);
            Route::get('/{id}/detail', [TugasController::class, 'ambilDetailTugas']);
            Route::get('/{id}/pending', [TugasController::class, 'ambilPenugasanPending']);
            Route::put('/penugasan/{id}/status', [TugasController::class, 'updateStatusPenugasan']);
            Route::post('/{id}/reminder', [BotController::class, 'kirimReminder']);
        });

        // Siswa only
        Route::middleware(['role:siswa'])->group(function () {
            Route::post('/{id}/submit', [TugasController::class, 'ajukanPenugasan']);
        });

        // Both guru and siswa
        Route::get('/', [TugasController::class, 'ambilTugas']);
    });

    // Bot reminder history (guru only)
    Route::prefix('bot')->middleware(['role:guru'])->group(function () {
        Route::get('/reminder/{idTugas}', [BotController::class, 'ambilReminder']);
    });
});
