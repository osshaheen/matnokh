<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Withdraw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** GET /api/dashboard — KPI tiles, charts and activity feeds for the home screen. */
    public function index(Request $request): JsonResponse
    {
        $days = min(max((int) $request->query('days', 14), 7), 90);
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();

        // Platform revenue is the SUBSCRIPTION income (order totals belong to the
        // merchants). Revenue is counted by when a subscription STARTS.
        return response()->json(['data' => [
            'kpis' => [
                'orders_today' => Order::whereDate('created_at', $today)->count(),
                'orders_total' => Order::count(),
                'orders_active' => Order::whereNotIn('status', Order::CLOSED)->count(),
                'drivers_available' => Driver::where('status', 'approved')->where('is_available', true)->count(),
                'drivers_total' => Driver::count(),
                'merchants_active' => Merchant::where('status', 'approved')->where('is_active', true)->count(),
                'merchants_pending' => Merchant::where('status', 'pending')->count(),
                'customers_total' => Customer::count(),
                'revenue_today' => round((float) Subscription::whereDate('starts_at', $today)->sum('price'), 2),
                'revenue_month' => round((float) Subscription::whereDate('starts_at', '>=', $monthStart)->sum('price'), 2),
                'withdraws_pending' => Withdraw::count(),
                'withdraws_pending_amount' => round((float) Withdraw::sum('amount'), 2),
                'subscriptions_active' => Subscription::where('status', 'active')->whereDate('ends_at', '>=', $today)->count(),
                'subscriptions_expiring' => Subscription::where('status', 'active')
                    ->whereBetween('ends_at', [$today, $today->copy()->addDays(7)])->count(),
            ],
            'orders_by_status' => $this->ordersByStatus(),
            'orders_chart' => $this->ordersChart($days),
            'top_merchants' => $this->topMerchants(),
            'top_drivers' => $this->topDrivers(),
            'recent_orders' => OrderResource::collection(
                Order::with(['customer', 'merchant', 'driver'])->latest('id')->limit(8)->get()
            ),
        ]]);
    }

    /** @return array<string,int> every status present as a key, zero-filled. */
    protected function ordersByStatus(): array
    {
        $counts = Order::select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')->pluck('aggregate', 'status');

        return collect(Order::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])
            ->all();
    }

    /** Orders + revenue per day, zero-filled so the chart has no gaps. */
    protected function ordersChart(int $days): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = Order::selectRaw('DATE(created_at) as day, count(*) as orders, sum(total) as revenue')
            ->where('created_at', '>=', $from)
            ->groupBy('day')->get()->keyBy('day');

        return collect(range(0, $days - 1))->map(function (int $i) use ($from, $rows) {
            $day = $from->copy()->addDays($i)->toDateString();
            $row = $rows->get($day);

            return [
                'day' => $day,
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
            ];
        })->all();
    }

    protected function topMerchants(): array
    {
        return Merchant::withCount(['orders as delivered_count' => fn ($q) => $q->where('status', 'delivered')])
            ->orderByDesc('delivered_count')->limit(5)->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->store_name,
                'orders' => (int) $m->delivered_count,
                'balance' => (float) $m->balance,
            ])->all();
    }

    protected function topDrivers(): array
    {
        return Driver::withCount(['orders as delivered_count' => fn ($q) => $q->where('status', 'delivered')])
            ->orderByDesc('delivered_count')->limit(5)->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'orders' => (int) $d->delivered_count,
                'rating' => (float) $d->rating,
            ])->all();
    }
}
