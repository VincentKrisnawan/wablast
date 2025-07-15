<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadContactController;
use App\Http\Controllers\MessageSessionController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\MessageController;



// Auth logic
Route::get('/', [AuthController::class, 'showLoginForm']);
Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');


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

