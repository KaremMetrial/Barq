<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Cart\Models\Cart;
use Symfony\Component\HttpFoundation\Response;

class CartMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve cart and inject into request
        $cart = $this->resolveCart($request);
        $request->merge(['resolved_cart' => $cart]);

        return $next($request);
    }

    /**
     * Resolve cart based on cart_key only
     */
    private function resolveCart(Request $request): ?Cart
    {
        $cartKey = $request->header('Cart-Key') ?? $request->input('cart_key');

        if ($cartKey) {
            return Cart::where('cart_key', $cartKey)
                ->with('items.product.store')
                ->first();
        }

        return null;
    }
}
