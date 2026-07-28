<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /** GET /api/settings — system-wide UI toggles. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'deletion_enabled' => true,
            'trash_enabled' => true,
            'restore_enabled' => true,
        ]]);
    }
}
