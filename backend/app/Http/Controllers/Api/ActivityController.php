<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    /** GET /api/activity — audit trail for the modules that opt into logging. */
    public function index(Request $request): JsonResponse
    {
        $activities = Activity::with('causer')
            ->when($request->query('log_name'), fn ($q, $name) => $q->where('log_name', $name))
            ->when($request->query('event'), fn ($q, $event) => $q->where('event', $event))
            ->latest('id')
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 100))
            ->withQueryString();

        return response()->json([
            'data' => collect($activities->items())->map(fn (Activity $a) => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'event' => $a->event,
                'description' => $a->description,
                'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->name,
                'changes' => $a->properties['attributes'] ?? [],
                'created_at' => $a->created_at,
            ]),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
