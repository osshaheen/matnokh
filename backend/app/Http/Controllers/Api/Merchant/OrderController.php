<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    use HandlesResourceQuery;

    // "current" = still in the merchant's hands; "completed" = handed over or closed.
    protected const CURRENT = ['pending', 'accepted', 'preparing', 'ready'];
    protected const COMPLETED = ['picked_up', 'on_the_way', 'delivered', 'canceled', 'rejected', 'returned'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $m = $request->attributes->get('merchant');
        $query = Order::where('merchant_id', $m->id)->with(['customer', 'branch', 'items']);

        // tab=current|completed
        $tab = $request->query('tab');
        if ($tab === 'current') $query->whereIn('status', self::CURRENT);
        elseif ($tab === 'completed') $query->whereIn('status', self::COMPLETED);

        $rows = $this->listing(
            $query, $request,
            searchable: ['order_no', 'recipient_name'],
            filters: ['status' => 'status'],
            sortable: ['id', 'created_at', 'status'],
        );

        return OrderResource::collection($rows);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->guard($request, $order);
        return (new OrderResource($order->load(['customer', 'branch', 'items', 'driver'])))->response();
    }

    /** POST /accept — قبول الطلب → تجهيز */
    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->guard($request, $order);
        $this->ensureStatus($order, 'pending');
        $order->update(['status' => 'preparing', 'accepted_at' => now()]);
        $order->statusLogs()->create(['status' => 'preparing', 'note' => 'قبل التاجر الطلب']);

        return response()->json(['message' => 'قبلت الطلب — جهّزه ثم أبلغ المناديب', 'status' => 'preparing']);
    }

    /** POST /reject — رفض الطلب → إعادة المبلغ للزبون */
    public function reject(Request $request, Order $order): JsonResponse
    {
        $this->guard($request, $order);
        $this->ensureStatus($order, 'pending');
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $order->update(['status' => 'rejected', 'cancel_reason' => $data['reason'] ?? 'رفض التاجر الطلب', 'canceled_at' => now()]);
        $order->statusLogs()->create(['status' => 'rejected', 'note' => $order->cancel_reason]);

        return response()->json(['message' => 'رفضت الطلب — سيُعاد المبلغ للزبون', 'status' => 'rejected']);
    }

    /** POST /ready — الطلب جاهز → بثّ لأقرب المناديب */
    public function ready(Request $request, Order $order): JsonResponse
    {
        $this->guard($request, $order);
        abort_unless(in_array($order->status, ['accepted', 'preparing'], true), 422, 'الطلب ليس قيد التجهيز');
        $order->update(['status' => 'ready']);
        $order->statusLogs()->create(['status' => 'ready', 'note' => 'المتجر جهّز الطلب — بثّ للمناديب']);

        return response()->json(['message' => 'بُثّ الطلب لأقرب المناديب ✓', 'status' => 'ready']);
    }

    protected function ensureStatus(Order $order, string $expected): void
    {
        abort_unless($order->status === $expected, 422, 'لا يمكن تنفيذ هذا الإجراء على حالة الطلب الحالية');
    }

    protected function guard(Request $request, Order $order): void
    {
        abort_unless($order->merchant_id === $request->attributes->get('merchant')->id, 404, 'الطلب غير موجود');
    }
}
