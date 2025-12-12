<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
// 1. IMPORT DUA BARIS INI (PENTING)
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 2. TAMBAHKAN KODE INI DI DALAM REGISTER
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            // Cek apakah request datang dari jalur '/api/*'
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data tidak ditemukan.',
                    'error_code' => 404
                ], 404);
            }
        });
    }
}
