<?php

use App\Http\Middleware\CheckPermission;

    use App\Http\Middleware\SetApiLocale;
    use Illuminate\Auth\Access\AuthorizationException;
    use Illuminate\Auth\AuthenticationException;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Database\QueryException;
    use Illuminate\Foundation\Application;
    use Illuminate\Foundation\Configuration\Exceptions;
    use Illuminate\Foundation\Configuration\Middleware;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Validation\ValidationException;
    use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
    use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
    use Illuminate\Http\Request;
    use Illuminate\Http\JsonResponse;
    use Laravel\Sanctum\Http\Middleware\CheckAbilities;
    use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        // Auto-close expired courier shifts every hour
        $schedule->command('shifts:auto-close-expired --hours=2')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();

        $schedule->command('cache:warm-courier-locations')
            ->everyTenMinutes()
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
          $middleware->append(SetApiLocale::class);
            $middleware->alias([
                'abilities' => CheckAbilities::class,
                'ability' => CheckForAnyAbility::class,
                'permission' => CheckPermission::class,
            ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404 - Not Found
            $exceptions->render(function (NotFoundHttpException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.page_not_found'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 404);
                }
            });

            // 404 - Model Not Found
            $exceptions->render(function (ModelNotFoundException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.record_not_found'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 404);
                }
            });

            // 405 - Method Not Allowed
            $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.method_not_allowed'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 405);
                }
            });
            // 422 - Validation Error
            $exceptions->render(function (ValidationException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    $errors = $e->errors();
                    $firstError = collect($errors)->flatten()->first();

                    return response()->json([
                        'success' => (bool)false,
                        'message' => $firstError ?: __('message.validation_failed'),
                        'data' => env('APP_DEBUG') ? $e->errors() : null,
                    ], 422);
                }
            });

            // 401 - Authentication Error
            $exceptions->render(function (AuthenticationException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => "Unauthorized access.",
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 401);
                }
            });

            // 403 - Authorization Error
            $exceptions->render(function (AuthorizationException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.access_forbidden'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 403);
                }
            });

            // 403 - Access Denied
            $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.access_denied'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 403);
                }
            });

            // 429 - Too Many Requests
            $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.rate_limit_exceeded'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 429);
                }
            });

            // Database Query Exception
            $exceptions->render(function (QueryException $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' => __('message.database_error'),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 500);
                }
            });

            // Generic exception handler - should be last
            $exceptions->render(function (Throwable $e, Request $request) {
                if ($request->is('api/*') || $request->is('admin/*')) {
                    return response()->json([
                        'success' => (bool)false,
                        'message' =>  $e->getMessage(),
                        'data' => env('APP_DEBUG') ? $e->getMessage() : null,
                    ], 422);
                }
            });
    })->create();
