<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\SeriesResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // 0. CARI USER (Public)
    // URL: GET /api/users?search=...
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($query, $search) use ($request) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });

                // Exclude current user from search results
                $user = $request->user('sanctum');
                if ($user) {
                    $query->where('id', '!=', $user->id);
                }
            })
            ->withCount(['posts', 'followers'])
            ->take(20)
            ->get();

        return UserResource::collection($users);
    }

    // 1. LIHAT PROFIL ORANG LAIN (Public)
    // URL: GET /api/users/{username}
    public function show(User $user)
    {
        // Hitung statistik (Post, Follower, Following)
        // loadCount jauh lebih ringan daripada load() -> count()
        $user->loadCount(['posts', 'followers', 'following']);

        // Kita return pakai UserResource yang sudah kita set "whenCounted" sebelumnya
        return new UserResource($user);
    }

    public function showById($id)
    {
        $user = User::findOrFail($id);
        $user->loadCount(['posts', 'followers', 'following']);
        return new UserResource($user);
    }

    // 2. LIHAT PROFIL SENDIRI (Private - via Token)
    // URL: GET /api/me
    public function me(Request $request)
    {
        $user = $request->user();

        // Load statistik punya sendiri
        $user->loadCount(['posts', 'followers', 'following']);

        return new UserResource($user);
    }

    // 3. UPDATE PROFIL SENDIRI
    // URL: POST/PUT /api/me
    // (Pakai POST kalau sekalian upload file, karena PUT kadang bermasalah dengan file di Laravel)
    // 3. UPDATE PROFIL SENDIRI
    // URL: POST /api/me (Gunakan POST, jangan PUT untuk upload file)
    public function update(Request $request)
    {
        $user = $request->user(); // Ambil user dari token

        // 1. Validasi
        $request->validate([
            'name'     => 'nullable|string|max:100',
            'bio'      => 'nullable|string|max:500',
            // Validasi username unik, KECUALI untuk user ini sendiri
            'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,

            // Validasi Gambar (File Fisik)
            // Backend menerima file mentah, terserah Flutter mau kirim hasil crop atau asli
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 2. Siapkan Data untuk Di-update (Hanya ambil field teks dulu)
        // Kita gunakan array_filter agar key yang null/tidak dikirim tidak menimpa data lama
        $dataToUpdate = array_filter($request->only(['name', 'bio', 'username']), function ($value) {
            return !is_null($value);
        });

        // 3. Logika Upload Avatar
        if ($request->hasFile('avatar')) {
            // A. Hapus file lama jika ada (Clean up storage)
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // B. Simpan file baru (Laravel otomatis kasih nama unik hash)
            // Masuk ke folder: storage/app/public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');

            // C. Masukkan path ke array update
            $dataToUpdate['avatar_url'] = $path;
        }

        // 4. Logika Upload Banner
        if ($request->hasFile('banner')) {
            // A. Hapus file lama
            if ($user->banner_url && Storage::disk('public')->exists($user->banner_url)) {
                Storage::disk('public')->delete($user->banner_url);
            }

            // B. Simpan file baru
            $path = $request->file('banner')->store('banners', 'public');

            // C. Masukkan path
            $dataToUpdate['banner_url'] = $path;
        }

        // 5. Eksekusi Update ke Database
        // Method update() otomatis mengisi kolom updated_at
        $user->update($dataToUpdate);

        // 6. Kembalikan Response User Terbaru
        return new UserResource($user);
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $request->validate([
            // 1. Email Baru: Wajib format email & Unik (kecuali punya sendiri)
            'email' => 'required|email|unique:users,email,' . $user->id,

            // 2. Password Konfirmasi: Wajib diisi & Harus cocok dengan password saat ini
            'password' => 'required|current_password',
        ]);

        // Kalau validasi lolos, berarti password benar. Langsung update.
        $user->update([
            'email' => $request->email
        ]);

        return response()->json([
            'message' => 'Email berhasil diperbarui',
            'user' => new UserResource($user), // Balikin data user biar UI update
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            // User wajib isi password lama
            'current_password' => 'required|current_password',
            // Password baru harus dikonfirmasi (ketik 2x)
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        // Update password di database (Jangan lupa di-Hash!)
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }

    // --- BAGIAN A: TAB PROFIL ORANG LAIN ---

    // 1. Tab Lembar (Postingan User Ini)
    // GET /api/users/{username}/posts
    public function posts(User $user)
    {
        $posts = $user->posts()->latest()->paginate(10);
        return PostResource::collection($posts);
    }

    // 2. Tab Jilid (Series User Ini)
    // GET /api/users/{username}/series
    public function series(User $user)
    {
        $series = $user->series()->with('posts')->latest()->paginate(10);
        return SeriesResource::collection($series);
    }

    // 3. Tab Suka (Yang di-Like User Ini)
    // GET /api/users/{username}/likes
    public function likes(User $user)
    {
        $likedPosts = $user->likedPosts()
            ->with('user')
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);
        return PostResource::collection($likedPosts);
    }

    // --- BAGIAN B: TAB PROFIL SAYA (/me) ---

    // 1. Tab Lembar Saya
    // GET /api/me/posts
    public function myPosts(Request $request)
    {
        $posts = $request->user()->posts()->latest()->paginate(10);
        return PostResource::collection($posts);
    }

    // 2. Tab Jilid Saya
    // GET /api/me/series
    public function mySeries(Request $request)
    {
        $series = $request->user()->series()->with('posts')->latest()->paginate(10);
        return SeriesResource::collection($series);
    }

    // 3. Tab Suka Saya
    // GET /api/me/likes
    public function myLikes(Request $request)
    {
        $likedPosts = $request->user()
            ->likedPosts()
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);
        return PostResource::collection($likedPosts);
    }
}
