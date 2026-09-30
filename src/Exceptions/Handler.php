<?php

namespace OpenDominion\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * {@inheritdoc}
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * {@inheritdoc}
     */
    protected function context()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function render($request, Throwable $exception)
    {
        if ($this->isPublicApiRequest($request)) {
            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'No ' . Str::snake(class_basename($exception->getModel()), ' ') . ' exists with that ID.',
                ], 404);
            }

            if ($exception instanceof ThrottleRequestsException) {
                return response()->json([
                    'error' => 'rate_limited',
                    'message' => 'Too many requests. Retry after the number of seconds in the Retry-After header.',
                ], 429, $exception->getHeaders());
            }

            return $this->renderPublicApiError($exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Any other error on the public V1 API is returned as JSON. HTTP errors keep
     * their status; anything unexpected is a generic 500 with no trace, even when
     * debug is on. The exception is still reported by report().
     */
    protected function renderPublicApiError(Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            return response()->json([
                'error' => match ($status) {
                    400 => 'bad_request',
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    503 => 'service_unavailable',
                    default => $status >= 500 ? 'server_error' : 'request_error',
                },
                'message' => $status >= 500
                    ? 'An unexpected error occurred.'
                    : (SymfonyResponse::$statusTexts[$status] ?? 'Request error.'),
            ], $status, $exception->getHeaders());
        }

        return response()->json([
            'error' => 'server_error',
            'message' => 'An unexpected error occurred.',
        ], 500);
    }

    /**
     * Whether the request is for the public V1 API (rounds and dominions endpoints),
     * whose errors all use the {"error": ..., "message": ...} format.
     */
    protected function isPublicApiRequest(Request $request): bool
    {
        return $request->route() !== null && $request->routeIs('api.rounds.*', 'api.dominions.*');
    }

    /**
     * {@inheritdoc}
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->guest(route('auth.login'));
    }
}
