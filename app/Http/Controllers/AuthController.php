<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource; // Pakai resource yang sudah kita buat
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

        // Kirim email verifikasi
        $user->sendEmailVerificationNotification();

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
        if (!$request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Verifikasi email terlebih dahulu'], 403);
        }
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'password' => 'required',
        ]);

        $user = $request->user();

        // Verifikasi password sebelum ganti email
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password salah'], 403);
        }

        // Buat token dan simpan pending email
        $token = Str::random(64);
        $user->pending_email = $request->email;
        $user->pending_email_token = hash('sha256', $token);
        $user->pending_email_expires_at = Carbon::now()->addDay();
        $user->save();

        // Kirim link konfirmasi ke email baru
        $verifyUrl = url('/api/email/change/' . $user->id . '/' . $token);
        if ($request->has('redirect')) {
            $verifyUrl .= '?redirect=' . urlencode($request->query('redirect'));
        }

        Mail::raw(
            "Halo {$user->name},\n\nKlik tautan berikut untuk mengonfirmasi perubahan email akun Lembar:\n{$verifyUrl}\n\nJika bukan Anda, abaikan email ini.",
            function ($message) use ($user) {
                $message->to($user->pending_email)
                    ->subject('Konfirmasi Perubahan Email Akun Lembar');
            }
        );

        return response()->json([
            'message' => 'Kami telah mengirim link konfirmasi ke email baru. Silakan cek email dan klik tautannya untuk menyelesaikan perubahan.',
        ]);
    }

    // 5. UPDATE PASSWORD
    public function updatePassword(Request $request)
    {
        if (!$request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Verifikasi email terlebih dahulu'], 403);
        }
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

    // Resend verification email
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link verifikasi telah dikirim']);
    }

    // Verify email from link
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);

        $redirectUrl = $request->query('redirect', env('VERIFICATION_REDIRECT_URL', 'lembar://verified'));

        if (!$user) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'User tidak ditemukan', 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'Link verifikasi tidak valid', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectOrJson($redirectUrl, 'already_verified', 'Email sudah terverifikasi', 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $this->redirectOrJson($redirectUrl, 'success', 'Email berhasil diverifikasi', 200);
    }

    // Konfirmasi perubahan email
    public function confirmEmailChange(Request $request, $id, $token)
    {
        $redirectUrl = $request->query('redirect', env('EMAIL_CHANGE_REDIRECT_URL', 'lembar://email-change'));
        $user = User::find($id);

        if (!$user) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'User tidak ditemukan', 404);
        }

        if (!$user->pending_email || !$user->pending_email_token) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'Tidak ada permintaan perubahan email', 400);
        }

        if ($user->pending_email_expires_at && Carbon::parse($user->pending_email_expires_at)->isPast()) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'Link sudah kedaluwarsa', 410);
        }

        if (!hash_equals($user->pending_email_token, hash('sha256', $token))) {
            return $this->redirectOrJson($redirectUrl, 'failed', 'Token tidak valid', 403);
        }

        // Finalisasi perubahan
        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->pending_email_token = null;
        $user->pending_email_expires_at = null;
        $user->save();

        return $this->redirectOrJson($redirectUrl, 'success', 'Email berhasil diubah', 200);
    }

    private function redirectOrJson(?string $redirectUrl, string $status, string $message, int $code)
    {
        if ($redirectUrl) {
            $separator = str_contains($redirectUrl, '?') ? '&' : '?';
            return redirect($redirectUrl . $separator . 'status=' . $status . '&message=' . urlencode($message));
        }

        return response()->json(['status' => $status, 'message' => $message], $code);
    }
}
