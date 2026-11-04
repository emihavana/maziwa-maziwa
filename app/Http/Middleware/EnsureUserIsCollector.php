<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsCollector
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['collector', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
