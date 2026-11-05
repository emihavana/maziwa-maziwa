<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KeepCollectorsOutOfAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'collector') {
            return redirect()->to('/collector');
        }

        return $next($request);
    }
}
