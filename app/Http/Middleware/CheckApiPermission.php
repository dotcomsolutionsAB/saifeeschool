<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckApiPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     return $next($request);
    // }

    // public function handle(Request $request, Closure $next, string $permission): Response
    // {
    //     $user = Auth::user();

    //     // Check if the user is authenticated and has the required permission
    //     if (!$user || !$user->can($permission)) {
    //         return response()->json(['message' => 'Forbidden: Insufficient Permissions'], 403);
    //     }

    //     return $next($request);
    // }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
       // \Log::info('CheckApiPermission middleware triggered.');

        $user = Auth::user();
        if (!$user || !$user->can($permission)) {
            return response()->json(['message' => 'Forbidden: Insufficient Permissions'], 403);
        }

        return $next($request);
    }

}
