<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Renders every exception thrown during an API request into the standard
 * envelope from docs/03-api-architecture.md:
 * { "success": false, "message": ..., "errors": {...}, "error_code": ... }
 */
class ApiExceptionRenderer
{
    public function render(Throwable $e, Request $request): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->envelope(
                status: 422,
                message: 'The given data was invalid.',
                errorCode: ErrorCodes::VALIDATION_ERROR,
                errors: $e->errors(),
            );
        }

        if ($e instanceof AuthenticationException) {
            return $this->envelope(401, 'Unauthenticated.', ErrorCodes::UNAUTHENTICATED);
        }

        if ($e instanceof AuthorizationException) {
            return $this->envelope(403, $e->getMessage() ?: 'This action is unauthorized.', ErrorCodes::FORBIDDEN);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->envelope(404, 'The requested resource was not found.', ErrorCodes::NOT_FOUND);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->envelope(429, 'Too many requests. Please try again later.', ErrorCodes::RATE_LIMITED);
        }

        if ($e instanceof ApiException) {
            return $this->envelope($e->status(), $e->getMessage(), $e->errorCode(), context: $e->context());
        }

        if ($e instanceof HttpExceptionInterface) {
            return $this->envelope($e->getStatusCode(), $e->getMessage() ?: 'Request failed.', ErrorCodes::INTERNAL_ERROR);
        }

        Log::error('Unhandled API exception', ['exception' => $e]);

        return $this->envelope(
            500,
            config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ErrorCodes::INTERNAL_ERROR,
        );
    }

    private function envelope(
        int $status,
        string $message,
        string $errorCode,
        array $errors = [],
        array $context = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        if (! empty($context)) {
            $payload += $context;
        }

        return response()->json($payload, $status);
    }
}
