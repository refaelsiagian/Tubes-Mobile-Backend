<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;

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




// --- ROUTE PUBLIK (Gak perlu login) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// --- ROUTE PROTECTED (Harus Login / Bawa Token) ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Logout harus login dulu
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Cek Profile Sendiri (Biasanya Flutter butuh ini buat mastiin token masih aktif)
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
    });
    
    // Nanti endpoint Create Post (Store), Update, Delete pindahin ke sini
    // biar cuma user login yang bisa posting!
    Route::apiResource('posts', PostController::class);

    // Route untuk Komentar (Nested Resource)
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    
    // Route Hapus (Gak perlu nested karena Comment ID sudah unik)
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});
