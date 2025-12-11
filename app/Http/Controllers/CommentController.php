<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    // 1. LIHAT DAFTAR KOMENTAR (Berdasarkan Post)
    // URL: GET /api/posts/{post}/comments
    public function index(Post $post)
    {
        // Ambil komentar milik post ini
        $comments = $post->comments()
            ->with('user') // Load data user biar UserResource gak kosong
            ->latest() // Urutkan dari yang terbaru
            ->paginate(10); // Paginasi biar gak berat

        return CommentResource::collection($comments);
    }

    // 2. KIRIM KOMENTAR BARU
    // URL: POST /api/posts/{post}/comments
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id, // Ambil ID user yang sedang login
            'content' => $request->input('content'),
        ]);

        // Load user supaya pas balikin response, data author-nya lengkap
        $comment->load('user');

        // 3. Return Resource dengan Tambahan Data
        return (new CommentResource($comment))
            ->additional([
                'meta' => [
                    'post_stats' => [
                        // Hitung ulang jumlah komentar terbaru
                        'comments_count' => $post->comments()->count()
                    ]
                ]
            ]);
    }

    // 3. HAPUS KOMENTAR
    // URL: DELETE /api/comments/{comment}
    public function destroy(Comment $comment, Request $request)
    {
        // Cek: Apakah yang mau hapus adalah pemilik komentar?
        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Komentar dihapus',
            'meta' => [
                'post_stats' => [
                    // Hitung ulang jumlah komentar terbaru
                    'comments_count' => $comment->post->comments()->count()
                ]
            ]
        ]);
    }
}
