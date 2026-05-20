<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
  
->withExceptions(function (Exceptions $exceptions): void {
    $apiError = function (
        Request $request,
        string $code,
        string $message,
        int $status,
        array $extra = []
    ): ?JsonResponse {
        if (! $request->is('api/*')) {
            return null;
        }

        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        return response()->json([
            'success' => false,
            'error' => array_merge([
                'code' => $code,
                'message' => $message,
            ], $extra),
            'meta' => [
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ],
        ], $status);
    };

    $exceptions->render(function (ValidationException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'VALIDATION_ERROR',
            message: 'Data yang diberikan tidak valid.',
            status: 422,
            extra: [
                'details' => $exception->errors(),
            ]
        );
    });

    $exceptions->render(function (AuthenticationException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'UNAUTHENTICATED',
            message: 'Anda harus login untuk mengakses resource ini.',
            status: 401
        );
    });

    $exceptions->render(function (AuthorizationException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'FORBIDDEN',
            message: $exception->getMessage() ?: 'Anda tidak memiliki izin untuk melakukan aksi ini.',
            status: 403
        );
    });

    $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'RESOURCE_NOT_FOUND',
            message: 'Resource tidak ditemukan.',
            status: 404
        );
    });

    $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($apiError) {
    if ($exception->getPrevious() instanceof ModelNotFoundException) {
        return $apiError(
            request: $request,
            code: 'RESOURCE_NOT_FOUND',
            message: 'Resource tidak ditemukan.',
            status: 404
        );
    }

    return $apiError(
        request: $request,
        code: 'ENDPOINT_NOT_FOUND',
        message: 'Endpoint API tidak ditemukan.',
        status: 404
    );
});

    $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'METHOD_NOT_ALLOWED',
            message: 'HTTP method tidak diizinkan untuk endpoint ini.',
            status: 405
        );
    });

    $exceptions->render(function (ThrottleRequestsException $exception, Request $request) use ($apiError) {
        return $apiError(
            request: $request,
            code: 'RATE_LIMIT_EXCEEDED',
            message: 'Terlalu banyak request. Silakan coba lagi nanti.',
            status: 429
        );
    });

$exceptions->render(function (\RuntimeException $exception, Request $request) use ($apiError) {
            return $apiError(
            request: $request,
            code: 'BUSINESS_RULE_VIOLATION',
            message: $exception->getMessage() ?: 'Request melanggar aturan bisnis.',
            status: 422
        );
    });

$exceptions->render(function (\Throwable $exception, Request $request) use ($apiError) {        $extra = [];

        if (config('app.debug')) {
            $extra['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $apiError(
            request: $request,
            code: 'INTERNAL_SERVER_ERROR',
            message: 'Terjadi kesalahan pada server.',
            status: 500,
            extra: $extra
        );
    });
})
  
->create();