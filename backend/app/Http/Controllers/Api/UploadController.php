<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    /** POST /api/uploads — store an image on the public disk, return its URL. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:4096'],
        ]);

        $path = $request->file('file')->store('uploads', 'public');
        $url = rtrim(config('app.url'), '/').'/storage/'.$path;

        return response()->json(['url' => $url, 'path' => $path]);
    }
}
