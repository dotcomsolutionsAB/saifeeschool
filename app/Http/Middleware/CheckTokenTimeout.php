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
            return response()->json(['message' => '❌ Unauthorized - token missing'], 401);
        }
    
        $token = $request->user()->currentAccessToken();
        $timeout = 10; // seconds for testing
        $lastUsed = $token->last_used_at ?? $token->created_at;
    
        return response()->json([
            'status' => '✅ Middleware reached',
            'last_used_at' => $lastUsed,
            'seconds_since_last_used' => now()->diffInSeconds($lastUsed),
            'timeout' => $timeout,
        ]);
    }
}