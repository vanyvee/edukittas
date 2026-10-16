<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Cache;
class UserAuthWare
{
    public function handle(Request $request, Closure $next):Response
    {
        $token = $request->cookie('user_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $cacheKey = 'sanctum_token_' . sha1($token);
    
        //  Try Redis first
        $accessToken = Cache::get($cacheKey);
    
        //  Fallback to DB
        if (!$accessToken) {
            $accessToken = PersonalAccessToken::findToken($token);
    
            if ($accessToken) {
                Cache::put($cacheKey, $accessToken, now()->addMinutes(30));
            }
        }
    
        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    
        $request->setUserResolver(function () use ($accessToken) {
            return $accessToken->tokenable;
        });
    
        return $next($request);
    }
}

