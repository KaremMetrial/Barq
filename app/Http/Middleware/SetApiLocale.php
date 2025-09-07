<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        if ($request->is('api/*')) {
            // Get language from header, default to config value
            $locale = $request->header('Accept-Language', config('app.locale'));

            // Validate the locale exists in supported languages
            if (in_array($locale, config('translatable.locales', ['en', 'ar']))) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}
