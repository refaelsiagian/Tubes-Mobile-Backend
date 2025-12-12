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

        // Assuming we just want a flat list of bookmarked posts for now
        // or we can use the 'Reading List' folder logic if implemented.
        // For simplicity matching the frontend 'Markah' page:
        
        $bookmarks = BookmarkItem::whereHas('folder', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['post.user'])
        ->latest()
        ->get();

        // Transform to match expected frontend format if needed, 
        // or just return the posts with proper stats
        $posts = $bookmarks->map(function($item) {
            $post = $item->post;
            // Load counts manually if not already loaded
            $post->loadCount(['comments', 'likes']);
            return $post;
        });

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

        $user = $request->user();
        
        // Find or create default "Reading List" folder
        $folder = $user->bookmarkFolders()->firstOrCreate(
            ['name' => 'Reading List'],
            ['user_id' => $user->id]
        );

        // Check if already bookmarked
        $exists = BookmarkItem::where('folder_id', $folder->id)
            ->where('post_id', $request->post_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Postingan sudah ada di markah',
            ], 409);
        }

        BookmarkItem::create([
            'folder_id' => $folder->id,
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
        $user = $request->user();
        
        // Find user's folders
        $folderIds = $user->bookmarkFolders()->pluck('id');

        $deleted = BookmarkItem::whereIn('folder_id', $folderIds)
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
