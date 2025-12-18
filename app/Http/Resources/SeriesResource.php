<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id, // String biar sama kayak contoh json kamu
            'title' => $this->title,
            'description' => $this->description,

            // --- TAMBAHAN AUTHOR ---
            // Kita gunakan whenLoaded biar gak error kalau lupa di-load di controller
            'author' => new UserResource($this->whenLoaded('user')),
            
            // Hitung jumlah lembar
            'count' => $this->posts->count(),
            
            // Ambil thumbnail dari lembar pertama (kalau ada)
            'thumbnail' => $this->posts->first() ? asset('storage/' . $this->posts->first()->thumbnail_url) : null,

            // --- INI BAGIAN LEMBAR ---
            // Kita gunakan PostResource agar datanya lengkap (likes, comments, dll)
            'posts' => PostResource::collection($this->whenLoaded('posts')),

            'updatedAt' => $this->updated_at,
            'createdAt' => $this->created_at,
        ];
    }
}
