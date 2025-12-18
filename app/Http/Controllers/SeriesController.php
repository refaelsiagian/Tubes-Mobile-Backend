<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;

use App\Http\Resources\SeriesResource;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $query = Series::query()
            ->with(['user', 'posts']); // Load posts for thumbnail and count

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $series = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => SeriesResource::collection($series),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'posts' => 'nullable|array', // Array of post IDs
            'posts.*' => 'exists:posts,id',
        ]);

        $series = Series::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // Attach posts with position
        $postsWithPosition = [];
        foreach ($request->posts as $index => $postId) {
            $postsWithPosition[$postId] = ['position' => $index + 1];
        }
        $series->posts()->attach($postsWithPosition);

        return response()->json([
            'success' => true,
            'data' => $series,
            'message' => 'Jilid berhasil dibuat',
        ], 201);
    }

    public function show($id)
    {
        $series = Series::with(['user', 'posts' => function($query) {
            $query->with(['user'])->withCount(['likes', 'comments']);
        }])->find($id);

        if (!$series) {
            return response()->json(['success' => false, 'message' => 'Jilid tidak ditemukan'], 404);
        }

        return new SeriesResource($series);
    }

    public function update(Request $request, $id)
    {
        $series = Series::find($id);
        if (!$series) {
            return response()->json(['success' => false, 'message' => 'Jilid tidak ditemukan'], 404);
        }

        // Validate
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'posts' => 'nullable|array',
            'posts.*' => 'exists:posts,id',
        ]);

        $series->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->has('posts')) {
            $postsWithPosition = [];
            foreach ($request->posts as $index => $postId) {
                $postsWithPosition[$postId] = ['position' => $index + 1];
            }
            $series->posts()->sync($postsWithPosition);
        }

        $series->load(['user', 'posts' => function($query) {
            $query->with(['user'])->withCount(['likes', 'comments']);
        }]);

        return (new SeriesResource($series))->additional(['message' => 'Jilid berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $series = Series::find($id);
        if (!$series) {
            return response()->json(['success' => false, 'message' => 'Jilid tidak ditemukan'], 404);
        }

        $series->delete();
        return response()->json(['success' => true, 'message' => 'Jilid berhasil dihapus']);
    }
}
