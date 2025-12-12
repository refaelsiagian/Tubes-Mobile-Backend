<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\SeriesResource;
use App\Models\Series;

class SeriesController extends Controller
{
    // 1. LIHAT DAFTAR SERIES
    public function index(Request $request)
    {
        $series = Series::with('posts.user') // Load post & authornya
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return SeriesResource::collection($series);
    }

    // 3. LIHAT DETAIL SATU JILID (SHOW)
    public function show(Series $series)
    {
        // Eager Loading: Kita load relasi 'posts' biar database gak berat.
        // Karena di model Series.php sudah kita set ->orderByPivot('position', 'asc'),
        // maka data 'posts' yang ditarik di sini OTOMATIS sudah berurutan 1, 2, 3...
        $series->load(['posts', 'user']);

        return new SeriesResource($series);
    }

    // 2. BUAT SERIES BARU / UPDATE (Logic Reorder ada di sini)
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            
            // Flutter mengirim array berisi ID postingan yang SUDAH DIURUTKAN
            // Contoh: [10, 5, 8] -> Artinya ID 10 posisi 1, ID 5 posisi 2, ID 8 posisi 3
            'posts' => 'required|array', 
            'posts.*' => 'exists:posts,id', // Pastikan ID post valid
        ]);

        $user = $request->user();

        // Buat Series
        $series = Series::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // --- LOGIKA REORDERING ---
        $syncData = [];
        
        // Loop array dari Flutter
        // $index adalah urutan array (0, 1, 2...)
        foreach ($request->posts as $index => $postId) {
            // Kita petakan: Post ID ini -> Posisinya adalah Index + 1
            $syncData[$postId] = ['position' => $index + 1];
        }

        // Simpan ke Pivot Table
        // Hasilnya di DB: ID 10 -> pos 1, ID 5 -> pos 2, dst.
        $series->posts()->sync($syncData);

        return new SeriesResource($series->load(['posts', 'user']));
    }

    // 3. UPDATE SERIES (Sama persis logic sync-nya)
    public function update(Request $request, Series $series)
    {
        // Pastikan punya sendiri
        if ($request->user()->id !== $series->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'posts' => 'required|array', // Array ID post yg sudah diurutkan baru
        ]);

        $series->update($request->only('title', 'description'));

        // --- LOGIKA REORDERING (SAMA) ---
        $syncData = [];
        foreach ($request->posts as $index => $postId) {
            $syncData[$postId] = ['position' => $index + 1];
        }
        
        $series->posts()->sync($syncData);

        return new SeriesResource($series->load(['posts', 'user']));
    }

    // 4. HAPUS SERIES
    public function destroy(Request $request, Series $series)
    {
        // A. Cek Kepemilikan (PENTING!)
        // Jangan sampai user A bisa menghapus Jilid milik user B
        if ($request->user()->id !== $series->user_id) {
            return response()->json(['message' => 'Unauthorized - Ini bukan jilid kamu'], 403);
        }

        // B. Lakukan Penghapusan
        // Karena di migration kamu sudah pasang onDelete('cascade'),
        // maka data di tabel pivot (series_posts) otomatis ikut terhapus bersih.
        $series->delete();

        return response()->json(['message' => 'Jilid berhasil dihapus']);
    }
}
