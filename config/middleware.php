<?php

return [
    'global' => [
        \Illuminate\Foundation\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    ],

    'route' => [
        'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'check-api-permission' => \App\Http\Middleware\CheckApiPermission::class, // Add this line
    ],
];
