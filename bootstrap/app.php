<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;
use Illuminate\Auth\{AuthenticationException, Access\AuthorizationException};
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $statusCode = $response->getStatusCode();

            if ($request->is('api/*')) {
                $message = 'Terjadi kesalahan pada server';
                $data = [];

                if ($exception instanceof ValidationException) {
                    $message = 'Validasi gagal';
                    $data = $exception->errors();
                } elseif ($exception instanceof AuthenticationException) {
                    $message = 'Tidak terautentikasi';
                } elseif ($exception instanceof AuthorizationException) {
                    $message = (string) $exception->getMessage();
                } elseif ($exception instanceof ModelNotFoundException || $statusCode === 404) {
                    $message = 'Data tidak ditemukan';
                } elseif ($statusCode === 403) {
                    $message = 'Tidak diizinkan';
                } elseif ($statusCode === 429) {
                    $message = 'Terlalu banyak permintaan';
                }

                Log::error('api_exception', [
                    'path' => (string) $request->path(),
                    'status' => (int) $statusCode,
                    'exception' => get_class($exception),
                    'message' => (string) $exception->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => $data,
                ], 200);
            }

            if (
                !$request->expectsJson()
                && !$request->is('api/*')
                && !$request->is('build/*')
                && !$request->is('storage/*')
                && in_array($statusCode, [503, 404, 403, 419], true)
            ) {
                return Inertia::render('ErrorPage', ['status' => $statusCode])
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            }

            return $response;
        });
    })->create();
