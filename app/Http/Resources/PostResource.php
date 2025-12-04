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
            'published_at' => $this->created_at->toFormattedDateString(), // Contoh format tanggal
        ];
    }
}
