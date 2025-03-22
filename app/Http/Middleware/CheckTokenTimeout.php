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

        // Use your custom column `my_last_updated_at`
        $timeout = 600; // 10 seconds for testing, change to 14400 for 4 hours
        $lastUsed = $token->my_last_updated_at ?? $token->created_at;
        $now = now();
        $elapsedSeconds = $now->diffInSeconds($lastUsed);

        // For debugging if `debug-timeout=true` is passed
        if ($request->has('debug-timeout')) {
            return response()->json([
                'status' => '✅ Middleware reached',
                'my_last_updated_at' => $lastUsed->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'seconds_since_last_used' => $elapsedSeconds,
                'timeout' => $timeout,
            ]);
        }

        if ($elapsedSeconds > $timeout) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Session Timeout: Please login again.',
            ], 401);
        }

        // ✅ Update custom column `my_last_updated_at`
        $token->forceFill(['my_last_updated_at' => $now])->save();

        return $next($request);
    }
}