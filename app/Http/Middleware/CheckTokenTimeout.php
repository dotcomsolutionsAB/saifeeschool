<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$request->user()->currentAccessToken()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $request->user()->currentAccessToken();

        // Timeout in seconds (4 hours = 14400 seconds)
        $timeout = 14400;

        $tokenCreated = $token->created_at;
        $lastUsed = $token->last_used_at ?? $tokenCreated;

        if (now()->diffInSeconds($lastUsed) > $timeout) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Session Timeout: Please login again.',
            ], 401);
        }

        return $next($request);
    }
}