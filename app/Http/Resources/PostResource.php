<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Cek user yang sedang login (kalau ada)
        $user = $request->user('sanctum');

        return [
            'id' => $this->id,
            'title' => $this->title,
            // 'thumbnail_url' => $this->thumbnail_url, // Jika ada
            'snippet' => $this->snippet ?? Str::limit(strip_tags($this->content), 100),
            'content' => $this->when(
                $request->routeIs('posts.show'),
                $this->content
            ),
            'author' => new UserResource($this->whenLoaded('user')),
            'stats' => [
                'likes' => (int) $this->likes_count,
                'comments' => (int) $this->comments_count,
            ],
            'thumbnail_url' => $this->thumbnail_url ? asset('storage/' . $this->thumbnail_url) : null,
            'published_at' => $this->created_at->toIso8601String(),
            'is_liked' => $user ? $this->likes()->where('user_id', $user->id)->exists() : false,
            'is_bookmarked' => $user
                ? $this->bookmarkedBy()->where('user_id', $user->id)->exists()
                : false,
        ];
    }
}
