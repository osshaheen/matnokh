<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $this->listing(
            Order::with(['customer', 'merchant', 'driver', 'city', 'service']),
            $request,
            searchable: ['order_no', 'recipient_name', 'recipient_phone', 'drop_address', 'customer.name', 'merchant.store_name'],
            filters: [
                'status' => 'status',
                'driver_id' => 'driver_id',
                'merchant_id' => 'merchant_id',
                'customer_id' => 'customer_id',
                'city_id' => 'city_id',
                'service_id' => 'service_id',
                'payment_method' => 'payment_method',
                'is_paid' => 'is_paid',
            ],
            sortable: ['id', 'order_no', 'total', 'status', 'created_at', 'delivered_at'],
        );

        return OrderResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'merchant', 'driver', 'city', 'service', 'statusLogs.user']);

        return (new OrderResource($order))->response();
    }

    public function store(Request $request): JsonResponse
    {
        if (! Setting::get('orders_enabled')) {
            $this->fail('status', 'استقبال الطلبات معطّل حالياً من الإعدادات');
        }

        $data = $this->validated($request);
        $data['commission'] ??= $this->defaultCommission($data);

        $order = DB::transaction(function () use ($data, $request) {
            $order = Order::create($data);
            $this->log($order, $order->status, 'إنشاء الطلب', $request);

            return $order;
        });

        return (new OrderResource($order->load(['customer', 'merchant', 'driver'])))
            ->response()->setStatusCode(201);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $order->update($this->validated($request, $order));

        return (new OrderResource($order->fresh()->load(['customer', 'merchant', 'driver'])))->response();
    }

    /** PATCH /api/orders/{order}/status */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === $order->status) {
            $this->fail('status', 'الطلب موجود بالفعل في هذه الحالة');
        }
        if (in_array($order->status, Order::CLOSED, true)) {
            $this->fail('status', 'لا يمكن تغيير حالة طلب منتهٍ');
        }
        if ($data['status'] !== 'pending' && ! $order->driver_id && ! in_array($data['status'], ['canceled', 'returned'], true)) {
            $this->fail('status', 'عيّن سائقاً للطلب قبل تحديث حالته');
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->markStatus($data['status']);
            if ($data['status'] === 'canceled') {
                $order->cancel_reason = $data['cancel_reason'] ?? $order->cancel_reason;
            }
            $order->save();

            if ($data['status'] === 'delivered') {
                $this->settleBalances($order);
            }

            $this->log($order, $data['status'], $data['note'] ?? null, $request);
        });

        return (new OrderResource($order->fresh()->load(['customer', 'merchant', 'driver', 'statusLogs.user'])))->response();
    }

    /** PATCH /api/orders/{order}/assign */
    public function assign(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['driver_id' => ['required', 'exists:drivers,id']]);
        $driver = Driver::findOrFail($data['driver_id']);

        if ($driver->status !== 'approved') {
            $this->fail('driver_id', 'السائق غير معتمد');
        }
        if (in_array($order->status, Order::CLOSED, true)) {
            $this->fail('driver_id', 'لا يمكن تعيين سائق لطلب منتهٍ');
        }

        DB::transaction(function () use ($order, $driver, $request) {
            $order->driver_id = $driver->id;
            if ($order->status === 'pending') {
                $order->markStatus('accepted');
            }
            $order->save();

            $this->log($order, $order->status, "تعيين السائق: {$driver->name}", $request);
        });

        return (new OrderResource($order->fresh()->load(['customer', 'merchant', 'driver', 'statusLogs.user'])))->response();
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->guardDeletion();
        $order->delete();

        return response()->json(['message' => 'تم نقل الطلب إلى سلّة المحذوفات']);
    }

    /** @return array<string,mixed> */
    protected function validated(Request $request, ?Order $order = null): array
    {
        $required = $order ? 'sometimes' : 'required';

        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'merchant_id' => ['nullable', 'exists:merchants,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'drop_address' => [$required, 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'items_total' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'commission' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'wallet'])],
            'is_paid' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'notes' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
    }

    /** Merchant's own rate when set, otherwise the platform default. */
    protected function defaultCommission(array $data): float
    {
        $rate = Merchant::find($data['merchant_id'] ?? null)?->commission_rate
            ?? Setting::get('default_commission_rate');

        return round(((float) ($data['items_total'] ?? 0)) * (float) $rate / 100, 2);
    }

    /** Delivery pays out: merchant keeps the goods minus commission, driver keeps the fee. */
    protected function settleBalances(Order $order): void
    {
        if ($order->merchant_id) {
            Merchant::whereKey($order->merchant_id)
                ->increment('balance', max(0, (float) $order->items_total - (float) $order->commission));
        }
        if ($order->driver_id) {
            Driver::whereKey($order->driver_id)->increment('balance', (float) $order->delivery_fee);
        }
    }

    protected function log(Order $order, string $status, ?string $note, Request $request): void
    {
        $order->statusLogs()->create([
            'status' => $status,
            'note' => $note,
            'user_id' => $request->user()?->id,
        ]);
    }
}
