<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            // Format waktu ala sosmed ("5 menit yang lalu")
            'created_at' => $this->created_at->diffForHumans(), 
            
            // REUSE: Panggil UserResource yang sudah kita buat sebelumnya
            'author' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
