<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /** GET /api/merchant/dashboard — home KPIs, 7-day sales, latest orders. */
    public function index(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $monthStart = Carbon::today()->startOfMonth();

        $monthSales = (float) $m->orders()->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $monthStart)->sum('items_total');

        // last 7 days of delivered sales
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $chart[] = [
                'date' => $day->toDateString(),
                'sales' => round((float) $m->orders()->where('status', 'delivered')->whereDate('delivered_at', $day)->sum('items_total'), 2),
            ];
        }

        return response()->json(['data' => [
            'store_name' => $m->store_name,
            'city' => $m->city?->name,
            'is_open' => (bool) $m->is_open,
            'kpis' => [
                'sales_month' => round($monthSales, 2),
                'branches' => $m->branches()->count(),
                'products' => $m->products()->count(),
                'rating' => (float) $m->rating,
                'orders_pending' => $m->orders()->where('status', 'pending')->count(),
                'orders_active' => $m->orders()->whereIn('status', ['pending', 'accepted', 'preparing', 'ready'])->count(),
            ],
            'sales_chart' => $chart,
            'latest_orders' => OrderResource::collection(
                $m->orders()->with(['customer', 'items'])->latest('id')->limit(6)->get()
            ),
        ]]);
    }
}
