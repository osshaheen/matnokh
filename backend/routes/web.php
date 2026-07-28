<?php

use Illuminate\Support\Facades\Route;

// API-only backend — the root just reports status (no session/view).
Route::get('/', fn () => response()->json([
    'app' => 'Wassilha API',
    'status' => 'ok',
    'ts' => now()->toIso8601String(),
]));
