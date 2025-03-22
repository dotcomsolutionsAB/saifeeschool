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

        if (!$user || !$request->user() || !$request->user()->currentAccessToken()) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Unauthorized: Invalid token or user not authenticated.'
            ], 401);
        }

        $token = $request->user()->currentAccessToken();

        // Timeout duration in seconds (for example, 10 seconds for testing)
        $timeout = 10;

        $lastUsedAt = $token->last_used_at ?? $token->created_at;

        // Parse using Carbon and ensure both times are in UTC
        $lastUsed = Carbon::parse($lastUsedAt)->timezone('UTC');
        $now = Carbon::now('UTC');

        $inactiveDuration = $now->diffInSeconds($lastUsed);

        // For testing/debugging purposes - remove in production
        if ($request->has('debug_timeout')) {
            return response()->json([
                'status' => '✅ Middleware reached',
                'last_used_at' => $lastUsed->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'seconds_since_last_used' => $inactiveDuration,
                'timeout' => $timeout
            ]);
        }

        if ($inactiveDuration > $timeout) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Session Timeout: Please login again.'
            ], 401);
        }

        return $next($request);
    }
}