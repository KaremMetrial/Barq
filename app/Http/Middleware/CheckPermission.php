<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $guard = $this->determineGuard($request);
        $user = auth($guard)->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->can($permission)) {
            return $this->forbiddenResponse($permission);
        }

        return $next($request);
    }

    private function determineGuard(Request $request): string
    {
        // Check by route name
        $routeName = $request->route()?->getName() ?? '';

        if (str_contains($routeName, 'admin.')) return 'admin';
        if (str_contains($routeName, 'vendor.')) return 'vendor';

        // Check by authenticated guard
        if (auth('admin')->check()) return 'admin';
        if (auth('vendor')->check()) return 'vendor';

        return 'user';
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required.',
            'error_code' => 'AUTH_REQUIRED',
            'data' => null
        ], 401);
    }

    private function forbiddenResponse(string $permission): JsonResponse
    {
        $user = auth($this->determineGuard(request()))->user();
        $userPermissions = [];

        if ($user && method_exists($user, 'getAllPermissions')) {
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        }

        return response()->json([
            'success' => false,
            'message' => 'Insufficient permissions to perform this action.',
            'error_code' => 'INSUFFICIENT_PERMISSIONS',
            'data' => [
                'required_permission' => $permission,
                'user_permissions' => $userPermissions
            ]
        ], 403);
    }
}
