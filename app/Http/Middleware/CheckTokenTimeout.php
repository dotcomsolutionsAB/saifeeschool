<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
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
        $timeout = 10; // 10 seconds for testing, change to 14400 for 4 hours

        $lastUsedAt = $token->my_last_updated_at ?? $token->created_at;

        // Ensure Carbon instance
        if (!$lastUsedAt instanceof Carbon) {
            $lastUsedAt = Carbon::parse($lastUsedAt);
        }

        $now = Carbon::now();
        $diff = now()->diffInRealSeconds($lastUsedAt, false); // includes microseconds

        // ⏱️ Timeout response
        if ($diff > $timeout) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => '⏱️ Session timed out. Please login again.',
                'last_used_at' => $lastUsedAt->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'seconds_since_last_used' => $diff,
                'timeout' => $timeout,
            ], 401);
        }

        // ✅ Update the token's last used timestamp
        $token->forceFill([
            'my_last_updated_at' => $now,
        ])->save();

        // 🔍 Optional debug response
        if ($request->has('debug-timeout') && $request->query('debug-timeout') === 'true') {
            return response()->json([
                'status' => '✅ Middleware triggered',
                'user_id' => $user->id,
                'token_id' => $token->id,
                'last_used_at' => $lastUsedAt->toDateTimeString(),
                'now' => $now->toDateTimeString(),
                'seconds_since_last_used' => $diff,
                'timeout' => $timeout,
            ]);
        }

        return $next($request);
    }
}