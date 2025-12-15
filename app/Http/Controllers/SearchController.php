<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Http\Resources\UserResource;
use App\Http\Resources\PostResource;

class SearchController extends Controller
{
    // 1. ENDPOINT UNTUK TAB PROFIL (Cari Orang)
    // GET /api/search/users?q=udin
    public function users(Request $request)
    {
        $keyword = $request->q; // Ambil kata kunci dari param ?q=...

        // Kalau keyword kosong, balikin kosong aja (hemat resource)
        if (!$keyword) {
            return UserResource::collection([]);
        }

        $users = User::query()
            // Logika Pencarian: Cari di Nama ATAU Username
            // Kita pakai where(function) biar logikanya terkurung (Grouping)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                      ->orWhere('username', 'like', "%{$keyword}%");
            })
            // JANGAN tampilkan diri sendiri di hasil pencarian
            ->where('id', '!=', $request->user('sanctum')?->id)
            
            // PENTING: Load statistik biar angka followers muncul di hasil search!
            ->withCount(['posts', 'followers', 'following'])
            
            // Urutkan (opsional, bisa latest atau by name)
            ->latest()
            
            // Pagination (Penting buat Infinite Scroll)
            ->paginate(10);

        return UserResource::collection($users);
    }

    // 2. ENDPOINT UNTUK TAB LEMBAR (Cari Karya)
    // GET /api/search/posts?q=tutorial
    public function posts(Request $request)
    {
        $keyword = $request->q;

        if (!$keyword) {
            return PostResource::collection([]);
        }

        $posts = Post::query()
            // Relasi ke penulisnya
            ->with('user')
            // Hitung like & komen biar tampil di kartu postingan
            ->withCount(['likes', 'comments'])
            
            // Pastikan cuma yang PUBLISHED yang muncul
            ->where('status', 'published') // Sesuaikan nama kolom status kamu
            
            // Logika Pencarian: Judul ATAU Isi
            ->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                      ->orWhere('content', 'like', "%{$keyword}%");
            })
            
            ->latest()
            ->paginate(10);

        return PostResource::collection($posts);
    }
}