<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Success-envelope helper for controllers, per docs/03-api-architecture.md.
 * Error responses are handled centrally by App\Exceptions\ApiExceptionRenderer.
 */
trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }
}
