<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Service;
use App\Models\StoreCategory;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

/**
 * Everything the dashboard's dropdowns need, in one request, so a form
 * doesn't fan out to eight list endpoints just to render its selects.
 */
class LookupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'cities' => City::where('is_active', true)->orderBy('sort')->orderBy('name')
                ->get(['id', 'name', 'delivery_fee']),
            'store_categories' => StoreCategory::where('is_active', true)->orderBy('sort')->orderBy('name')
                ->get(['id', 'name']),
            'services' => Service::where('is_active', true)->orderBy('sort')->orderBy('name')
                ->get(['id', 'name', 'base_price']),
            'subscription_plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort')
                ->get(['id', 'name', 'price', 'duration_days']),
            'drivers' => Driver::where('status', 'approved')->orderBy('name')
                ->get(['id', 'name', 'phone', 'is_available', 'city_id']),
            'merchants' => Merchant::where('status', 'approved')->orderBy('store_name')
                ->get(['id', 'store_name as name', 'phone', 'city_id']),
            'enums' => [
                'order_status' => Order::STATUSES,
                'payment_method' => ['cash', 'card', 'wallet'],
                'vehicle_type' => DriverController::VEHICLES,
                'partner_status' => DriverController::STATUSES,
                'withdraw_status' => ['pending', 'approved', 'rejected', 'paid'],
                'withdraw_method' => ['bank', 'wallet', 'cash'],
                'subscription_status' => ['active', 'expired', 'canceled'],
                'banner_position' => BannerController::POSITIONS,
                'audience' => NotificationController::AUDIENCES,
            ],
        ]]);
    }
}
