<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait HandlesApiExceptions
{
    protected function apiTry(callable $fn, Request $request, array $context = []): JsonResponse
    {
        try {
            $response = $fn();
            if ($response instanceof JsonResponse) {
                $response->setStatusCode(200);
            }
            return $response;
        } catch (ValidationException $e) {
            Log::error('api_validation_failed', array_merge($this->baseContext($request, $e), $context));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $e->errors(),
            ], 200);
        } catch (AuthorizationException $e) {
            Log::error('api_authorization_failed', array_merge($this->baseContext($request, $e), $context));
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 200);
        } catch (AuthenticationException $e) {
            Log::error('api_authentication_failed', array_merge($this->baseContext($request, $e), $context));
            return response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi',
                'data' => [],
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('api_model_not_found', array_merge($this->baseContext($request, $e), $context));
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'data' => [],
            ], 200);
        } catch (\Throwable $e) {
            $ctx = array_merge($this->baseContext($request, $e), ['trace' => $e->getTraceAsString()], $context);
            Log::error('api_unhandled_exception', $ctx);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
                'data' => [],
            ], 200);
        }
    }

    private function baseContext(Request $request, \Throwable $e): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ];
    }
}
