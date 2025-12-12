<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
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
        $series = Series::all();

        // --- BAGIAN INI YANG DIPERBAIKI (Likes) ---
        foreach ($posts as $post) {
            // Ambil random user (maksimal 15 atau sejumlah user yg ada)
            $userCount = $users->count();
            $maxInteractions = min($userCount, 15);

            if ($maxInteractions > 0) {
                $interactingUsers = $users->random(rand(min(5, $maxInteractions), $maxInteractions));

                foreach ($interactingUsers as $user) {
                    // 1. Buat Comment (Aman karena pakai factory)
                    Comment::factory()->create([
                        'post_id' => $post->id,
                        'user_id' => $user->id
                    ]);

                    // 2. Buat Like (ERROR SEBELUMNYA DI SINI)
                    // Cek dulu biar gak error unique constraint (post_id + user_id)
                    // Jika user ini belum like post ini, baru create.
                    if (!$post->likes()->where('user_id', $user->id)->exists()) {

                        // GANTI attach() MENJADI create()
                        $post->likes()->create([
                            'user_id' => $user->id
                        ]);
                    }
                }
            }
        }

        // --- Buat Item Bookmark ---
        foreach ($users as $user) {
            $postCount = $posts->count();
            $maxBookmarks = min($postCount, 7); // Max 7 bookmark per user
            
            if ($maxBookmarks > 0) {
                // Ambil beberapa post acak
                $postsToBookmark = $posts->random(rand(0, $maxBookmarks));
                
                // Masukkan ke tabel bookmark_items via relasi bookmarkedPosts
                // (Pastikan kamu sudah nambahin fungsi bookmarkedPosts di User.php tadi ya)
                $user->bookmarkedPosts()->syncWithoutDetaching($postsToBookmark);
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
