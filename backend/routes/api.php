<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

// public
Route::post('login', [AuthController::class, 'login']);

// authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('settings', [SettingsController::class, 'index']);
});
