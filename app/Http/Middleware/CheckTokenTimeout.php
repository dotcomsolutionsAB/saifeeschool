<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenTimeout
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$request->user()->currentAccessToken()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $request->user()->currentAccessToken();

        // Get the timeout duration (10 seconds for testing, change to 14400 for 4hrs)
        $timeout = 60;

        // Get token from DB to access custom column `my_last_updated_at`
        $dbToken = PersonalAccessToken::find($token->id);
        $lastUsed = $dbToken->my_last_updated_at ? Carbon::parse($dbToken->my_last_updated_at) : Carbon::parse($dbToken->created_at);
        $now = now();

        $diffInSeconds = $lastUsed->diffInSeconds($now);

        // 🧪 Debug mode via query param
        if ($request->has('debug-timeout')) {
            return response()->json([
                'status' => '✅ Middleware triggered',
                'user_id' => $user->id,
                'token_id' => $token->id,
                'last_used_at' => $lastUsed->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'seconds_since_last_used' => $diffInSeconds,
                'timeout' => $timeout
            ]);
        }

        // ❌ Inactive too long
        if ($diffInSeconds > $timeout) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Session Timeout: Please login again.',
            ], 401);
        }

        // ✅ Update token usage timestamp
        $dbToken->my_last_updated_at = $now;
        $dbToken->save();

        return $next($request);
    }
}