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
        $user = auth()->user();

        // Collectors are bounced to /collector, EXCEPT on Filament's auth pages
        // (email verification prompt/verify and logout). Blocking those causes a redirect loop.
        // if ($user?->role === 'collector' && ! $request->routeIs('filament.admin.auth.*')) {
        //     return redirect('/collector');
        // }

        return $next($request);
    }
}
