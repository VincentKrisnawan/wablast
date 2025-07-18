<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadContactController;
use App\Http\Controllers\MessageSessionController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HomeController;



// Auth logic
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Definisikan rute untuk menampilkan form login di URL /login dan beri nama 'login'.
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');



Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Upload kontak
    Route::post('/upload-contacts', [UploadContactController::class, 'upload']);
    Route::get('/upload-batches/{id}/contacts', [UploadContactController::class, 'getContacts']);

    // Sesi
    Route::post('/upload-batches/{id}/generate-sessions', [MessageSessionController::class, 'generateSessions']);
    Route::get('/upload-batches/{id}/sessions', [MessageSessionController::class, 'getSessions']);

    // Template pesan
    Route::post('/sessions/{id}/template', [MessageTemplateController::class, 'store']);
    Route::get('/sessions/{id}/template', [MessageTemplateController::class, 'show']);

    // Kirim pesan dan laporan
    Route::post('/session/{session}/send', [MessageController::class, 'send'])->name('session.send');
    Route::get('/sessions/{id}/report', [MessageController::class, 'report']);

    Route::get('/home', function () {
        return view('pages.home');
    });
    // Route untuk menampilkan halaman utama
    // Mengarah ke method 'index' di HomeController
    // Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // Route untuk menangani proses upload file
    // Mengarah ke method 'upload' di HomeController
    Route::post('/upload', [HomeController::class, 'upload'])->name('upload.file');

    // Route untuk menyimpan template pesan
    // Mengarah ke method 'storeTemplate' di HomeController
    Route::post('/template/store', [HomeController::class, 'storeTemplate'])->name('template.store');

    Route::post('/cleanup', [HomeController::class, 'cleanup'])->name('data.cleanup');

    // {batch} akan otomatis di-resolve menjadi instance UploadBatch
    // Route::get('/batch/{batch}/contacts', [HomeController::class, 'showContacts'])->name('contacts.show');
    Route::delete('/session/{session}/delete', [HomeController::class, 'destroySession'])->name('session.destroy');
    Route::get('/contacts', [HomeController::class, 'showAllContacts'])->name('contacts.all');

    // Dashboard and Session Details
    Route::get('/dashboard', [App\Http\Controllers\MessagesInsightController::class, 'dashboard'])->name('dashboard');
    Route::get('/sessions/{sessionId}/details', [App\Http\Controllers\MessagesInsightController::class, 'sessionDetails'])->name('session.details');

    Route::post('/session/{session}/send', [HomeController::class, 'sendSession'])->name('session.send');
    Route::get('/session/{session}/status', [HomeController::class, 'getSessionStatus'])->name('session.status');
});



