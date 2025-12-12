<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use App\Models\BookmarkItem;
use App\Models\Post;

class BookmarkController extends Controller
{

    // 1. TOGGLE BOOKMARK (Simpan/Hapus)
    // Kita pakai fitur toggle() bawaan relasi belongsToMany biar singkat
    public function toggle(Request $request, Post $post)
    {
        // Gunakan relasi bookmarkedPosts() yang ada di User.php
        // toggle() otomatis cek: kalau ada -> hapus, kalau gak ada -> simpan.
        $changes = $request->user()->bookmarkedPosts()->toggle($post->id);

        // attached berisi array ID yang baru disimpan. 
        // Kalau kosong, berarti barusan dihapus (detached).
        $isBookmarked = count($changes['attached']) > 0;

        return response()->json([
            'message' => $isBookmarked ? 'Disimpan ke bookmark' : 'Dihapus dari bookmark',
            'is_bookmarked' => $isBookmarked
        ]);
    }

    // 2. LIHAT DAFTAR BOOKMARK
    public function index(Request $request)
    {
        // PERBAIKAN 2: Langsung panggil Post via relasi bookmarkedPosts
        // Ini JAUH lebih efisien daripada ambil BookmarkItem dulu terus di-map
        $posts = $request->user()
            ->bookmarkedPosts() // Panggil relasi belongsToMany di User.php
            ->with('user') // Eager load author postingan biar ringan
            ->latest('bookmark_items.created_at') // Urutkan berdasarkan kapan disimpannya
            ->paginate(10); // Pagination aman terkendali

        // Return collection langsung, pagination metadata (page, total) TETAP ADA
        return PostResource::collection($posts);
    }
}
