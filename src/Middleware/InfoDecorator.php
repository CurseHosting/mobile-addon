<?php

namespace App\MobileAddon\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class InfoDecorator
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('X-Module-Version', config('mobileaddon.version'));
        $response->header('X-CurseHosting-Version', config('cursehosting.version'));

        if (Auth::check()) {
            $user = Auth::user();
            $response->header('X-Root-Admin', $user->root_admin ? 'true' : 'false');
        }
        return $response;
    }
}

