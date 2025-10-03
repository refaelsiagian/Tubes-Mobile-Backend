<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\BookmarkFolder;
use App\Models\Series;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Truncating all tables...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Post::truncate();
        Comment::truncate();
        BookmarkFolder::truncate();
        Series::truncate();
        DB::table('likes')->truncate();
        DB::table('follows')->truncate();
        DB::table('bookmark_items')->truncate();
        DB::table('series_posts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('All old data has been truncated!');
        
        $this->call([
            UserSeeder::class,        // 1. Buat pengguna.
            ContentSeeder::class,     // 2. Buat konten milik pengguna.
            InteractionSeeder::class, // 3. Buat interaksi antar pengguna dan konten.
        ]);

        $this->command->info('Database seeding completed successfully! 🎉');
    }
}