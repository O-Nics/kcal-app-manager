<?php

use App\Support\Middleware\TransformRequestMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            TransformRequestMiddleware::class,
        ]);
       $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability'   => CheckForAnyAbility::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 🎯 Cas 401 : non authentifié
        $exceptions->render(function (AuthenticationException $e, $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas connecté.",
            ], 401);
        });

        // 🎯 Tous les autres cas
        $exceptions->render(function (Throwable $e, $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            $isDebug = (bool) config('app.debug');
            $rawMessage = trim($e->getMessage() ?: '');
            $safeMessage = 'Une erreur est survenue.';

            $message = $isDebug
                ? ($rawMessage !== '' ? $rawMessage : $safeMessage)
                : ($rawMessage !== '' && mb_strlen($rawMessage) <= 80 ? $rawMessage : $safeMessage);

            Log::error('Exception non catchée', [
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
                'status'    => $status,
                'url'       => $request->fullUrl(),
                'method'    => $request->method(),
                'user_id'   => optional($request->user())->id,
                'ip'        => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
                'error'   => $isDebug ? $rawMessage : null,
                'trace'   => $isDebug ? $e->getTrace() : null,
            ], $status);
        });
    })
    ->create();
