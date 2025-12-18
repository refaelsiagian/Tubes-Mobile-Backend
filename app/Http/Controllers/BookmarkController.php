<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookmarkItem;
use App\Models\Post;

class BookmarkController extends Controller
{
    // Get user's bookmarks
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $bookmarks = BookmarkItem::where('user_id', $userId)
            ->with(['post.user'])
            ->latest()
            ->get();

        $posts = $bookmarks->map(function($item) {
            $post = $item->post;
            if ($post) {
                $post->loadCount(['comments', 'likes']);
            }
            return $post;
        })->filter();

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\PostResource::collection($posts),
        ]);
    }

    // Add bookmark
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);

        $userId = $request->user()->id;
        
        // Check if already bookmarked
        $exists = BookmarkItem::where('user_id', $userId)
            ->where('post_id', $request->post_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Postingan sudah ada di markah',
            ], 409);
        }

        BookmarkItem::create([
            'user_id' => $userId,
            'post_id' => $request->post_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil ditambahkan ke markah',
        ]);
    }

    // Remove bookmark
    public function destroy(Request $request, $postId)
    {
        $userId = $request->user()->id;
        
        $deleted = BookmarkItem::where('user_id', $userId)
            ->where('post_id', $postId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil dihapus dari markah',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Markah tidak ditemukan',
            ], 404);
        }
    }
}
