<?php

namespace App\Http\Middleware;

use Closure;

class SecretKey
{
    public function handle($request, Closure $next)
    {
        if ($request->header('X-Secret-Key') !== 'listed1234@@') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
