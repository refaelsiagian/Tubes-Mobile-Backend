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
            // Kita rename 'posts' jadi 'lembar' sesuai request kamu
            'lembar' => $this->posts->map(function ($post) {
                return [
                    'id' => (string) $post->id,
                    'title' => $post->title,
                    // Potong konten jadi snippet
                    'snippet' => substr(strip_tags($post->content), 0, 50), 
                    'date' => $post->created_at->diffForHumans(),
                    // Debugging: liat posisinya bener gak
                    'position' => $post->pivot->position, 
                ];
            }),

            'updatedAt' => $this->updated_at,
            'createdAt' => $this->created_at,
        ];
    }
}
