<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get language from header, default to config value
        $locale = $request->header('Accept-Language', config('app.locale'));
        // Set the locale - translatable package will handle fallback for unsupported locales
        if (in_array($locale, config('translatable.locales', ['en', 'ar']))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
