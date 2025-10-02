<?php

namespace Database\Factories;

use App\Models\BookmarkFolder;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookmarkItem>
 */

class BookmarkItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folder_id' => BookmarkFolder::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
