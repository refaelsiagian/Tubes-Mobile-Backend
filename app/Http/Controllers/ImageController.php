<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'image' => 'required|image|max:2048', // Maks 2MB
            // Kita validasi folder apa saja yang boleh (biar hacker gak aneh-aneh)
            'folder' => 'required|string|in:avatars,banners,thumbnails,posts',
        ]);

        if ($request->hasFile('image')) {
            // 2. Tentukan Folder Tujuan
            // Kalau dari Flutter gak kirim 'folder', defaultnya masuk ke 'others'
            $folderName = $request->input('folder');

            // 3. Simpan Gambar ke folder spesifik
            // Hasil path nanti: "avatars/namafile.jpg" atau "posts/namafile.jpg"
            $path = $request->file('image')->store($folderName, 'public');

            // 4. Buat URL
            $url = asset('storage/' . $path);

            return response()->json([
                'url' => $url,
                'message' => 'Gambar berhasil diunggah ke ' . $folderName
            ], 201);
        }

        return response()->json(['message' => 'Gagal'], 400);
    }
}
