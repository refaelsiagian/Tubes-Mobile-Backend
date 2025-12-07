<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. DATA DASAR (Selalu Muncul)
            // Ini murah, karena ambil dari kolom tabel users langsung
            'id' => $this->id,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url
                ? asset('storage/' . $this->avatar_url)
                : 'https://ui-avatars.com/api/?name=' . urlencode($this->username),
            'banner_url' => $this->banner_url
                ? asset('storage/' . $this->banner_url)
                : null,

            // 2. DATA TAMBAHAN (Kondisional)
            // Bio & Join Date cuma teks, tidak berat, tapi kalau mau disembunyikan
            // di list post biar JSON lebih kecil, bisa pakai logika route.
            'bio' => $this->when(!$request->routeIs('posts.*'), $this->bio),
            'joined_at' => $this->when(!$request->routeIs('posts.*'), fn() => $this->created_at->toFormattedDateString()),

            // 3. STATISTIK BERAT (Kuncinya di Sini!)
            // Kita pakai '$this->whenCounted'.
            // Artinya: Key 'stats' HANYA akan muncul di JSON kalau Controller
            // secara eksplisit memanggil 'withCount'.
            'stats' => $this->when($this->posts_count !== null, function () {
                return [
                    'posts' => (int) $this->posts_count,
                    'followers' => (int) ($this->followers_count ?? 0),
                ];
            }),
        ];
    }
}
