<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidationBatchController;
use App\Http\Controllers\ValidationProfileController;
use Illuminate\Support\Facades\Route;

// Halaman awal diarahkan ke login atau dashboard jika sudah login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Autentikasi Pengguna
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Area Terproteksi (Login Diperlukan)
Route::middleware(['auth', 'role'])->group(function () {
    // Dashboard Utama
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Unggah Berkas & Pratinjau (Dapat diakses Petugas & Admin)
    Route::get('/batches/dummy-template', [ValidationBatchController::class, 'downloadDummy'])->name('batches.dummy-template');
    Route::get('/batches', [ValidationBatchController::class, 'index'])->name('batches.index');
    Route::get('/batches/upload', [ValidationBatchController::class, 'create'])->name('batches.create');
    Route::post('/batches/upload', [ValidationBatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}/preview', [ValidationBatchController::class, 'preview'])->name('batches.preview');

    // Eksekusi & Hasil Validasi
    Route::post('/batches/{batch}/validate', [\App\Http\Controllers\ValidationExecutionController::class, 'execute'])->name('validation.execute');
    Route::get('/batches/{batch}/results', [\App\Http\Controllers\ValidationExecutionController::class, 'results'])->name('validation.results');
    Route::get('/batches/{batch}/rows/{row}/edit', [\App\Http\Controllers\ValidationExecutionController::class, 'editRow'])->name('validation.edit-row');
    Route::put('/batches/{batch}/rows/{row}', [\App\Http\Controllers\ValidationExecutionController::class, 'updateRow'])->name('validation.update-row');
    Route::post('/batches/{batch}/revalidate', [\App\Http\Controllers\ValidationExecutionController::class, 'revalidate'])->name('validation.revalidate');

    // Khusus Admin
    Route::middleware('role:admin')->group(function () {
        // Kelola Pengguna
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('users', UserController::class);

        // Kelola Profil & Aturan Validasi
        Route::get('/profiles', [ValidationProfileController::class, 'index'])->name('profiles.index');
        Route::get('/profiles/{profile}', [ValidationProfileController::class, 'show'])->name('profiles.show');
        Route::patch('/profiles/{profile}/toggle-status', [ValidationProfileController::class, 'toggleStatus'])->name('profiles.toggle-status');
        Route::patch('/profiles/{profile}/rules/{rule}/toggle-status', [ValidationProfileController::class, 'toggleRuleStatus'])->name('profiles.rules.toggle-status');
        Route::get('/profiles/{profile}/rules/{rule}/edit', [ValidationProfileController::class, 'editRule'])->name('profiles.rules.edit');
        Route::put('/profiles/{profile}/rules/{rule}', [ValidationProfileController::class, 'updateRule'])->name('profiles.rules.update');
    });
});
