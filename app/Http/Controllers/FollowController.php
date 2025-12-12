<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user)
    {
        $currentUser = $request->user();

        // 1. Validasi: Gak boleh follow diri sendiri
        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'Tidak bisa follow diri sendiri'], 400);
        }

        // 2. Magic Toggle (Bawaan Laravel untuk Many-to-Many)
        // Fungsi ini otomatis nge-cek:
        // - Kalau belum ada -> Di-attach (Follow)
        // - Kalau sudah ada -> Di-detach (Unfollow)
        // Return value-nya array yang ngasih tau apa yang terjadi ('attached' atau 'detached')
        $changes = $currentUser->following()->toggle($user->id);

        // 3. Cek Status Sekarang
        // Kalau array 'attached' ada isinya, berarti barusan berhasil Follow
        $isFollowing = count($changes['attached']) > 0;

        // 4. Return Data untuk Update UI Flutter
        return response()->json([
            'message' => $isFollowing ? 'Berhasil mengikuti' : 'Berhenti mengikuti',
            'status'  => $isFollowing ? 'following' : 'not_following',
            
            // Kita balikin jumlah follower terbaru si target, biar angka di profil dia berubah
            'new_follower_count' => $user->followers()->count()
        ]);
    }
}
