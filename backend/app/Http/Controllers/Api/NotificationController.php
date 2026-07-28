<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\PushNotificationResource;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    use HandlesResourceQuery;

    public const AUDIENCES = ['all', 'customers', 'drivers', 'merchants'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->listing(
            PushNotification::with('creator'),
            $request,
            searchable: ['title', 'body'],
            filters: ['status' => 'status', 'audience' => 'audience'],
            sortable: ['id', 'title', 'sent_at', 'created_at'],
        );

        return PushNotificationResource::collection($notifications);
    }

    public function show(PushNotification $notification): JsonResponse
    {
        return (new PushNotificationResource($notification->load('creator')))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $send = $request->boolean('send_now');

        $notification = PushNotification::create([
            ...$data,
            'status' => 'draft',
            'created_by' => $request->user()?->id,
        ]);

        if ($send) {
            $this->dispatchNotification($notification);
        }

        return (new PushNotificationResource($notification->load('creator')))->response()->setStatusCode(201);
    }

    public function update(Request $request, PushNotification $notification): JsonResponse
    {
        if ($notification->status === 'sent') {
            $this->fail('id', 'لا يمكن تعديل إشعار تم إرساله');
        }

        $notification->update($this->validated($request, $notification));

        return (new PushNotificationResource($notification->fresh()->load('creator')))->response();
    }

    /** POST /api/notifications/{notification}/send */
    public function send(PushNotification $notification): JsonResponse
    {
        if ($notification->status === 'sent') {
            $this->fail('id', 'تم إرسال هذا الإشعار مسبقاً');
        }

        $this->dispatchNotification($notification);

        return (new PushNotificationResource($notification->fresh()->load('creator')))->response();
    }

    /** GET /api/notifications/audience-size?audience=drivers */
    public function audienceSize(Request $request): JsonResponse
    {
        $request->validate(['audience' => ['required', Rule::in(self::AUDIENCES)]]);

        $probe = new PushNotification(['audience' => $request->query('audience')]);

        return response()->json(['data' => ['size' => $probe->audienceSize()]]);
    }

    public function destroy(PushNotification $notification): JsonResponse
    {
        $this->guardDeletion();
        $notification->delete();

        return response()->json(['message' => 'تم نقل الإشعار إلى سلّة المحذوفات']);
    }

    /**
     * Marks the notification as sent and records how many accounts it reached.
     * Actual delivery (FCM/SMS) plugs in here once a provider is configured.
     */
    protected function dispatchNotification(PushNotification $notification): void
    {
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $notification->audienceSize(),
        ]);
    }

    protected function validated(Request $request, ?PushNotification $notification = null): array
    {
        $required = $notification ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'body' => [$required, 'string', 'max:2000'],
            'audience' => ['nullable', Rule::in(self::AUDIENCES)],
            'target_ids' => ['nullable', 'array'],
            'target_ids.*' => ['integer'],
        ]);
    }
}
