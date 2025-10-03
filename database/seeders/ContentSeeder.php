<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\BookmarkFolder;
use App\Models\Series;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating primary content (Posts, Folders, Series)...');
        
        $users = User::all(); // Ambil semua user yang sudah dibuat
        
        // Loop setiap user untuk membuat konten milik mereka
        foreach ($users as $user) {
            // Setiap user membuat antara 1 sampai 5 post
            Post::factory(rand(1, 5))->create(['user_id' => $user->id]);
            
            // Setiap user membuat antara 1 sampai 3 folder bookmark
            BookmarkFolder::factory(rand(1, 3))->create(['user_id' => $user->id]);
            
            // Ada 50% kemungkinan seorang user membuat 1 series
            if (rand(0, 1) === 1) {
                Series::factory()->create(['user_id' => $user->id]);
            }
        }
        
        $this->command->info('Primary content has been created!');
    }
}