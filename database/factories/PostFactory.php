<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */

class PostFactory extends Factory
{
    public function definition(): array
    {
        $dummyContent = [
            [
                "insert" => "Ini Judul",
                "attributes" => [
                    "bold" => true
                ]
            ],
            [
                "insert" => "\n",
                "attributes" => [
                    "header" => 2
                ]
            ],
            [
                "insert" => "Desk\n\n"
            ],
            [
                "insert" => "Ini bold",
                "attributes" => [
                    "bold" => true
                ]
            ],
            [
                "insert" => "\n"
            ],
            [
                "insert" => "Ini italic (miring)",
                "attributes" => [
                    "italic" => true
                ]
            ],
            [
                "insert" => "\n"
            ],
            [
                "insert" => "Ini underline",
                "attributes" => [
                    "underline" => true
                ]
            ],
            [
                "insert" => "\nIni Block Quote "
            ],
            [
                "insert" => "\n",
                "attributes" => [
                    "blockquote" => true
                ]
            ],
            [
                "insert" => "Ini Bullet list "
            ],
            [
                "insert" => "\n",
                "attributes" => [
                    "list" => "bullet"
                ]
            ],
            [
                "insert" => "Ini numbered list"
            ],
            [
                "insert" => "\n",
                "attributes" => [
                    "list" => "ordered"
                ]
            ],
            [
                "insert" => "\n"
            ]
        ];
        
        $randomDate = fake()->dateTimeBetween('-2 months', 'now');

        return [
            // 'user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'content' => json_encode($dummyContent),
            'snippet' => fake()->text(100), 
            'status' => fake()->randomElement(['draft', 'published']),
            'created_at' => $randomDate,
            'updated_at' => $randomDate,
        ];
    }
}
