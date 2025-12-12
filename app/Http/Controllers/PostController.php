<?php

namespace App\Http\Controllers;
use App\Http\Resources\PostResource;
use App\Models\Post;


use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Post::query()
            ->with('user')
            ->withCount(['comments', 'likes']);

        // If user_id is specified, show all posts (including drafts and private) for that user's profile
        // Otherwise (HomePage), only show published AND public posts
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        } else {
            $query->where('status', 'published')
                  ->where('visibility', 'public');
        }

        $posts = $query->latest()->paginate(15);

        return PostResource::collection($posts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No implementation needed for API controllers
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'visibility' => 'nullable|in:public,private',
            'snippet' => 'nullable|string|max:500',
            // 'thumbnail_url' => 'nullable|url',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'content' => $request->input('content'),
            'status' => $request->status,
            'visibility' => $request->visibility ?? 'public',
            'snippet' => $request->snippet,
            // 'thumbnail_url' => $request->thumbnail_url,
        ]);

        $post->load('user');
        
        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load('user')->loadCount(['comments', 'likes']);
        return new PostResource($post);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        // Pakai route show
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // Check if user owns the post
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validasi - allow partial updates, all fields optional
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'status' => 'nullable|in:draft,published',
            'visibility' => 'nullable|in:public,private',
            'snippet' => 'nullable|string|max:500',
        ]);

        // Only update fields that are provided (not null and not empty string)
        $updateData = [];
        foreach (['title', 'content', 'status', 'visibility', 'snippet'] as $field) {
            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                $updateData[$field] = $request->$field;
            }
        }

        // Update Database - only update provided fields
        if (!empty($updateData)) {
            $post->update($updateData);
        }
        
        $post->load('user');
        $post->loadCount(['comments', 'likes']);

        // 3. Load User (PENTING)
        // Sama seperti di store, kita load relasi user agar 
        // key 'author' tetap muncul di JSON response (karena logic whenLoaded di Resource).
        // Ini biar Frontend gak kaget/error karena tiba-tiba data author hilang pas habis edit.
        $post->load('user');

        // 4. Return Resource
        return new PostResource($post);
    }


    /**
     * Get posts liked by the authenticated user
     */
    public function likedPosts(Request $request)
    {
        $user = $request->user();
        
        $likedPosts = Post::whereHas('likes', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with('user')
        ->withCount(['comments', 'likes'])
        ->latest()
        ->get();
        
        return PostResource::collection($likedPosts);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Optional: Check ownership
        // if ($post->user_id !== request()->user()->id) { ... }

        $post->delete();
        return response()->json(['message' => 'Post deleted successfully']);
    }
}
