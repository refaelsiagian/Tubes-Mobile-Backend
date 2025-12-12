<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Wajib import ini buat hapus/simpan file

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::query()
            ->with('user')
            // Gunakan withCount untuk query builder (lebih efisien dari loadCount)
            ->withCount(['comments', 'likes'])
            ->where('status', 'published')
            ->latest()
            ->paginate(15);

        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:draft,published',
            'snippet' => 'nullable|string|max:500',
            // Ubah validasi jadi FILE IMAGE
            'thumbnail' => 'nullable|image|max:2048', // Max 2MB, format jpg/png
        ]);

        $user = $request->user(); // Ambil user dari token

        // 2. Handle Upload Thumbnail
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            // Simpan ke folder: storage/app/public/thumbnails
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        // 3. Simpan ke Database
        $post = Post::create([
            'user_id'       => $user->id, // Pakai ID user yang login
            'title'         => $request->title,
            'content'       => $request->input('content'),
            'status'        => $request->status,
            'snippet'       => $request->snippet,
            'thumbnail_url' => $thumbnailPath, // Simpan path-nya saja
        ]);

        $post->load('user'); // Load user untuk response

        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        // Pakai loadCount di sini benar karena $post sudah berupa Model (bukan query builder)
        $post->load('user')->loadCount(['comments', 'likes']);
        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // 1. Cek Authorisasi (Opsional tapi PENTING)
        // Pastikan yang edit adalah pemilik postingan
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 2. Validasi
        $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'required|string',
            'status'    => 'required|in:draft,published',
            'snippet'   => 'nullable|string|max:500',
            'thumbnail' => 'nullable|image|max:2048', // Validasi file
        ]);

        // 3. Siapkan Data Text
        $dataToUpdate = [
            'title'   => $request->title,
            'content' => $request->input('content'), // Jangan pakai request->  content,
            'status'  => $request->status,
            'snippet' => $request->snippet,
        ];

        // 4. Handle Ganti Thumbnail
        if ($request->hasFile('thumbnail')) {
            // A. Hapus thumbnail lama jika ada (Biar storage gak penuh sampah)
            if ($post->thumbnail_url && Storage::disk('public')->exists($post->thumbnail_url)) {
                Storage::disk('public')->delete($post->thumbnail_url);
            }

            // B. Upload yang baru
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            
            // C. Masukkan ke array update
            $dataToUpdate['thumbnail_url'] = $path;
        }

        // 5. Eksekusi Update
        $post->update($dataToUpdate);

        // 6. Return response
        // Load ulang user biar data author gak hilang di JSON response
        return new PostResource($post->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Post $post)
    {
        // 1. Cek Authorisasi
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 2. Hapus File Thumbnail dari Storage (Bersih-bersih)
        if ($post->thumbnail_url && Storage::disk('public')->exists($post->thumbnail_url)) {
            Storage::disk('public')->delete($post->thumbnail_url);
        }

        // 3. Hapus Data dari DB
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}