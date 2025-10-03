<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\BookmarkFolder;
use App\Models\Series;

class InteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating interactions (Likes, Comments, Follows, etc.)...');
        
        $users = User::all();
        $posts = Post::all();
        $folders = BookmarkFolder::all();
        $series = Series::all();
        
        // --- Buat Komentar dan Likes ---
        foreach ($posts as $post) {
            $userCount = $users->count();
            $maxInteractions = min($userCount, 15); // Jangan minta lebih dari jumlah user yang ada
            $interactingUsers = $users->random(rand(5, $maxInteractions));
            
            foreach ($interactingUsers as $user) {
                Comment::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
                $post->likes()->attach($user->id);
            }
        }

        // --- Buat Item Bookmark ---
        foreach ($folders as $folder) {
            $postCount = $posts->count();
            $maxBookmarks = min($postCount, 7); // Jangan minta lebih dari jumlah post yang ada
            if ($maxBookmarks > 0) {
                $postsToBookmark = $posts->random(rand(1, $maxBookmarks));
                $folder->posts()->attach($postsToBookmark);
            }
        }

        // --- Isi Post ke dalam Series ---
        foreach ($series as $s) {
            $postsForSeriesCollection = $posts->where('user_id', $s->user_id);
            $availablePostsCount = $postsForSeriesCollection->count();
            $maxPostsInSeries = min($availablePostsCount, 5); // Jangan minta lebih dari post yang dimiliki user

            if ($maxPostsInSeries > 0) {
                $numberToPick = rand(1, $maxPostsInSeries);
                $postsForSeries = $postsForSeriesCollection->random($numberToPick);
                $position = 1;
                foreach ($postsForSeries as $post) {
                    $s->posts()->attach($post->id, ['position' => $position++]);
                }
            }
        }

        // --- Buat Relasi Follow ---
        foreach ($users as $user) {
            $otherUsersCount = $users->count() - 1;
            $maxFollowing = min($otherUsersCount, 10); // Jangan minta lebih dari jumlah user lain
            if ($maxFollowing > 0) {
                $numberToFollow = rand(1, $maxFollowing);
                $usersToFollow = $users->where('id', '!=', $user->id)->random($numberToFollow);
                $user->following()->attach($usersToFollow);
            }
        }
        
        $this->command->info('Interactions have been created!');
    }
}