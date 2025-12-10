<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class LikeController extends Controller
{
    // PostLikeController.php

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        // Cek apakah user ini sudah ada di tabel likes untuk post ini?
        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            // SUDAH ADA -> HAPUS (Unlike)
            $like->delete();
            $isLiked = false;
        } else {
            // BELUM ADA -> BUAT BARU (Like)
            // Kita pakai create() dari relasi, user_id diambil dari request user
            $post->likes()->create([
                'user_id' => $user->id
            ]);
            $isLiked = true;
        }

        return response()->json([
            'message'   => $isLiked ? 'Disukai' : 'Batal disukai',
            'is_liked'  => $isLiked,    // Buat ubah warna icon di Flutter
            'new_count' => $post->likes()->count() // Buat update angka
        ]);
    }
}
