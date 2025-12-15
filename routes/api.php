<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\SeriesController;


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
Route::post('/check-username', [AuthController::class, 'checkUsername']);
Route::get('/users', [UserController::class, 'index']);


// --- ROUTE PROTECTED (Harus Login / Bawa Token) ---
Route::middleware('auth:sanctum')->group(function () {

    // Logout harus login dulu
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cek Profile Sendiri (Biasanya Flutter butuh ini buat mastiin token masih aktif)
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
    });

    // Update Email & Password
    Route::put('/user/email', [AuthController::class, 'updateEmail']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    // Nanti endpoint Create Post (Store), Update, Delete pindahin ke sini
    // biar cuma user login yang bisa posting!
    Route::apiResource('posts', PostController::class);

    // Route untuk Komentar (Nested Resource)
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);

    // Route Hapus (Gak perlu nested karena Comment ID sudah unik)
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Route Upload Gambar
    Route::post('/upload-image', [ImageController::class, 'upload']);

    // Endpoint Toggle Like
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);

    Route::get('/users/{user}', [UserController::class, 'show']); // {user} ini nanti isinya username
    // 2. Route Profil Sendiri ("Me")
    Route::get('/me', [UserController::class, 'me']);     // Lihat profil sendiri
    Route::post('/me', [UserController::class, 'update']); // Edit profil sendiri

    // 3. Ganti Email (Butuh email baru & pass saat ini)
    Route::put('/me/email', [UserController::class, 'updateEmail']);

    // 2. Ganti Password (Butuh pass lama & baru)
    Route::put('/me/password', [UserController::class, 'updatePassword']);

    // 4. Route Follow / Unfollow
    Route::post('/users/{user}/follow', [FollowController::class, 'toggle']);

    // Bookmark
    Route::apiResource('bookmarks', BookmarkController::class);

    // CRUD Series (Jilid)
    // Contoh data input:
    // {
    //     "title": "Koleksi Belajar Masakkkkk",
    //     "description": "Resep andalan saya",
    //     "posts": [8, 2, 5, 3]   // id post yang sudah diurutkan
    // }
    Route::apiResource('series', SeriesController::class);

    // 1. Tab Profil Orang Lain
    Route::get('/users/{user}/posts',  [UserController::class, 'posts']);
    Route::get('/users/{user}/series', [UserController::class, 'series']);
    Route::get('/users/{user}/likes',  [UserController::class, 'likes']);

    // 2. Tab Profil Saya
    Route::get('/me/posts',  [UserController::class, 'myPosts']);
    Route::get('/me/series', [UserController::class, 'mySeries']);
    Route::get('/me/likes',  [UserController::class, 'myLikes']);
});
