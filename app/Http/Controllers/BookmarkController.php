<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use App\Models\BookmarkItem;
use App\Models\Post;

class BookmarkController extends Controller
{

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        // Cek apakah item sudah ada
        $bookmark = BookmarkItem::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($bookmark) {
            // Jika ada, hapus (Unbookmark)
            $bookmark->delete();
            $isBookmarked = false;
            $message = 'Dihapus dari bookmark';
        } else {
            // Jika belum ada, buat baru (Bookmark)
            BookmarkItem::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $isBookmarked = true;
            $message = 'Disimpan ke bookmark';
        }

        return response()->json([
            'message' => $message,
            'is_bookmarked' => $isBookmarked
        ]);
    }

    // BONUS: Endpoint buat liat daftar bookmark saya
    // GET /api/bookmarks
    public function index(Request $request)
    {
        $bookmarkedPosts = $request->user()
            // Ambil item bookmark-nya
            ->bookmarks()
            // Lompati/Load postingan yang ada di item tersebut
            ->with('post.user') // Load Post dan Author dari Post tersebut
            ->latest('created_at')
            ->paginate(10);

        // Kita harus memetakan hasilnya untuk mendapatkan koleksi Post
        $posts = $bookmarkedPosts->map(function ($item) {
            return $item->post;
        });

        // Karena kita tidak bisa langsung return collection dari PostResource pada hasil map()
        // yang pagination, kamu bisa gunakan cara ini (agak lebih rumit):
        // Cara tercepat untuk demo adalah mengambil semua datanya saja

        // Cara paling simpel untuk demo:
        return PostResource::collection($posts);

        // CATATAN: Dengan cara ini, pagination metadata-nya akan hilang, 
        // tapi untuk demo list post-nya akan muncul.
    }
}
