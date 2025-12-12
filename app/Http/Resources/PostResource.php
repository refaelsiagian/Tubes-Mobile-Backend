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
            // 'snippet' => substr($this->content, 0, 100) . '...', // Contoh snippet
            'content' => $this->when(
                !$request->routeIs('posts.index'),
                $this->content
            ),
            'author' => new UserResource($this->whenLoaded('user')),
            'stats' => [
                'likes' => (int) $this->likes_count,
                'comments' => (int) $this->comments_count,
            ],
            'published_at' => $this->created_at?->toIso8601String(), // ISO format for Flutter parsing
            'status' => $this->status, // 'draft' or 'published'
            'visibility' => $this->visibility, // 'public' or 'private'
            'is_liked' => $user ? $this->likes()->where('user_id', $user->id)->exists() : false,
            'is_bookmarked' => $user ? \App\Models\BookmarkItem::where('post_id', $this->id)
                ->whereHas('folder', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->exists() : false,
        ];
    }
}
