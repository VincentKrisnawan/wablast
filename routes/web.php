<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

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
Route::post('/sessions/{id}/send', [MessageController::class, 'send']);
Route::get('/sessions/{id}/report', [MessageController::class, 'report']);

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');