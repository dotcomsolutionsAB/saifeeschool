<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckTokenTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$request->user()->currentAccessToken()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $request->user()->currentAccessToken();

        $timeout = 600; // 10 seconds for testing
        $now = now();

        // Convert to Carbon instance safely
        $lastUsed = $token->my_last_updated_at 
                    ? Carbon::parse($token->my_last_updated_at) 
                    : $token->created_at;

        $elapsedSeconds = $now->diffInSeconds($lastUsed);

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