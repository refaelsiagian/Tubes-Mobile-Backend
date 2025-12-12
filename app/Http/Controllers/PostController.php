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
    public function index()
    {
        $posts = Post::query()
            ->with('user')
            ->loadCount(['comments', 'likes'])
            ->where('status', 'published')
            ->latest()
            ->paginate(15);

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
            // 'snippet' => 'nullable|string|max:500',
            // 'thumbnail_url' => 'nullable|url',
        ]);

        $post = Post::create([
            'user_id' => 1,
            'title' => $request->title,
            'content' => $request->input(`content`), // Atau $request->content,
            'status' => $request->status,
            // 'snippet' => $request->snippet,
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
        // 1. Validasi
        // Aturannya mirip store, tapi biasanya kita bolehkan kalau user
        // cuma mau update sebagian (misal judul doang), 
        // tapi untuk blog editor biasanya dikirim ulang semua datanya, jadi 'required' tetap aman.
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            // 'thumbnail_url' => 'nullable|url',
        ]);

        // 2. Update Database
        // Kita tidak perlu set 'user_id' lagi karena penulisnya tidak berubah.
        $post->update([
            'title' => $request->title,
            'content' => $request->input('content'), // Atau $request->content,
            'status' => $request->status,
            // 'thumbnail_url' => $request->thumbnail_url,
        ]);

        // 3. Load User (PENTING)
        // Sama seperti di store, kita load relasi user agar 
        // key 'author' tetap muncul di JSON response (karena logic whenLoaded di Resource).
        // Ini biar Frontend gak kaget/error karena tiba-tiba data author hilang pas habis edit.
        $post->load('user');

        // 4. Return Resource
        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
