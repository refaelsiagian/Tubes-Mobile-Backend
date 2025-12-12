<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource; // Pakai resource yang sudah kita buat

class AuthController extends Controller
{
    // Check Username Availability
    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
        ]);

        $exists = User::where('username', $request->username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Username sudah dipakai' : 'Username tersedia'
        ]);
    }

    // 1. REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        // Buat User Baru
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Password wajib di-hash
            'name' => $request->name,
            // 'avatar_url' => null, // Default null, nanti UserResource yang kasih default avatar
        ]);

        // Bikin Token (KTP) buat si User
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => new UserResource($user), // Konsisten pakai UserResource
            'token' => $token, // INI PENTING: Token untuk disimpan di HP
        ], 201);
    }

    // 2. LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah email & password cocok
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        // Kalau cocok, ambil datanya
        $user = User::where('email', $request->email)->firstOrFail();

        // Bikin Token Baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    // 3. LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai (biar gak bisa dipake lagi)
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    // 4. UPDATE EMAIL
    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'password' => 'required',
        ]);

        $user = $request->user();

        // Verifikasi password sebelum ganti email
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password salah'], 403);
        }

        $user->email = $request->email;
        $user->save();

        return response()->json([
            'message' => 'Email berhasil diubah',
            'user' => new UserResource($user),
        ]);
    }

    // 5. UPDATE PASSWORD
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $user = $request->user();

        // Verifikasi password lama
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah']);
    }
}
