<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookmarkItem;
use App\Models\Post;
use App\Http\Resources\PostResource;

class BookmarkController extends Controller
{
    // 1. GET LIST MARKAH (Diperbaiki formatnya)
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Ambil bookmark milik user, beserta data Post dan Penulisnya
        $bookmarks = BookmarkItem::where('user_id', $userId)
            ->with(['post.user', 'post.likes', 'post.comments']) // Load relasi penting
            ->latest()
            ->get();

        // TRANSFORMASI DATA (PENTING!)
        // Kita keluarkan object 'post' dari dalam 'bookmark'
        // Supaya format JSON-nya sama persis dengan halaman Home
        $posts = $bookmarks->map(function ($item) {
            $post = $item->post;
            
            // Kalau post sudah dihapus tapi bookmark masih ada, kita skip (opsional)
            if (!$post) return null;

            // Tambahkan info manual
            $post->is_bookmarked = true; 
            
            // Format stats biar Flutter gak error baca null
            $post->stats = [
                'likes' => $post->likes->count(),
                'comments' => $post->comments->count()
            ];

            return $post;
        })->filter(); // Hapus yang null

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts) // Reset index array
        ]);
    }

    // 2. TOGGLE (Simpan/Hapus)
    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $existing = BookmarkItem::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true, 
                'message' => 'Dihapus dari markah', 
                'is_bookmarked' => false
            ]);
        } else {
            BookmarkItem::create([
                'user_id' => $user->id,
                'post_id' => $post->id
            ]);
            return response()->json([
                'success' => true, 
                'message' => 'Disimpan ke markah', 
                'is_bookmarked' => true
            ]);
        }
    }
}