<?php

namespace App\MobileAddon\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use App\MobileAddon\Models\MobileToken;

class TokenAuth
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('X-Token');
        $uuid = $request->header('X-Device-Identifier');

        if ($token && $uuid) {
            if ($appToken = MobileToken::validFor($uuid)->whereToken($token)->first()) {
                $appToken->expires_at = now()->addDays(2);
                $appToken->save();

                Auth::onceUsingId($appToken->user->id);
                return $next($request);
            }
        }

        throw new AuthenticationException(
            'Unauthenticated.'
        );
    }

}
