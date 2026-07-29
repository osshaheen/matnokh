<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StoreCategoryController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TrashController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

// public
Route::post('login', [AuthController::class, 'login']);

// authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // available to every signed-in user — the UI needs these to render itself
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('uploads', [UploadController::class, 'store']);
    Route::get('lookups', [LookupController::class, 'index']);

    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('perm:dashboard.view');

    // ── orders ────────────────────────────────────────────────────────────
    Route::get('orders', [OrderController::class, 'index'])->middleware('perm:order.view');
    Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('perm:order.view');
    Route::post('orders', [OrderController::class, 'store'])->middleware('perm:order.create');
    Route::put('orders/{order}', [OrderController::class, 'update'])->middleware('perm:order.update');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('perm:order.update');
    Route::patch('orders/{order}/assign', [OrderController::class, 'assign'])->middleware('perm:order.update');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->middleware('perm:order.delete');

    // ── drivers ───────────────────────────────────────────────────────────
    Route::get('drivers', [DriverController::class, 'index'])->middleware('perm:driver.view');
    Route::get('drivers/{driver}', [DriverController::class, 'show'])->middleware('perm:driver.view');
    Route::post('drivers', [DriverController::class, 'store'])->middleware('perm:driver.create');
    Route::post('drivers/{driver}/password', [DriverController::class, 'setPassword'])->middleware('perm:driver.update');
    Route::put('drivers/{driver}', [DriverController::class, 'update'])->middleware('perm:driver.update');
    Route::patch('drivers/{driver}/status', [DriverController::class, 'updateStatus'])->middleware('perm:driver.update');
    Route::patch('drivers/{driver}/availability', [DriverController::class, 'updateAvailability'])->middleware('perm:driver.update');
    Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->middleware('perm:driver.delete');

    // ── merchants ─────────────────────────────────────────────────────────
    Route::get('merchants', [MerchantController::class, 'index'])->middleware('perm:merchant.view');
    Route::get('merchants/{merchant}', [MerchantController::class, 'show'])->middleware('perm:merchant.view');
    Route::post('merchants', [MerchantController::class, 'store'])->middleware('perm:merchant.create');
    Route::post('merchants/{merchant}/password', [MerchantController::class, 'setPassword'])->middleware('perm:merchant.update');
    Route::put('merchants/{merchant}', [MerchantController::class, 'update'])->middleware('perm:merchant.update');
    Route::patch('merchants/{merchant}/status', [MerchantController::class, 'updateStatus'])->middleware('perm:merchant.update');
    Route::delete('merchants/{merchant}', [MerchantController::class, 'destroy'])->middleware('perm:merchant.delete');

    // ── customers ─────────────────────────────────────────────────────────
    Route::get('customers', [CustomerController::class, 'index'])->middleware('perm:customer.view');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->middleware('perm:customer.view');
    Route::post('customers', [CustomerController::class, 'store'])->middleware('perm:customer.create');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('perm:customer.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('perm:customer.delete');

    // ── withdraws ─────────────────────────────────────────────────────────
    Route::get('withdraws', [WithdrawController::class, 'index'])->middleware('perm:withdraw.view');
    Route::get('withdraws/{withdraw}', [WithdrawController::class, 'show'])->middleware('perm:withdraw.view');
    Route::post('withdraws', [WithdrawController::class, 'store'])->middleware('perm:withdraw.create');
    Route::delete('withdraws/{withdraw}', [WithdrawController::class, 'destroy'])->middleware('perm:withdraw.delete');

    // ── subscriptions & plans ─────────────────────────────────────────────
    Route::get('subscription-plans', [SubscriptionPlanController::class, 'index'])->middleware('perm:subscription.view');
    Route::post('subscription-plans', [SubscriptionPlanController::class, 'store'])->middleware('perm:subscription.create');
    Route::put('subscription-plans/{plan}', [SubscriptionPlanController::class, 'update'])->middleware('perm:subscription.update');
    Route::delete('subscription-plans/{plan}', [SubscriptionPlanController::class, 'destroy'])->middleware('perm:subscription.delete');

    Route::get('subscriptions', [SubscriptionController::class, 'index'])->middleware('perm:subscription.view');
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->middleware('perm:subscription.view');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])->middleware('perm:subscription.create');
    Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->middleware('perm:subscription.create');
    Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->middleware('perm:subscription.update');
    Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->middleware('perm:subscription.delete');

    // ── content: cities, categories, services, banners, articles ──────────
    Route::get('cities', [CityController::class, 'index'])->middleware('perm:city.view');
    Route::post('cities', [CityController::class, 'store'])->middleware('perm:city.create');
    Route::put('cities/{city}', [CityController::class, 'update'])->middleware('perm:city.update');
    Route::delete('cities/{city}', [CityController::class, 'destroy'])->middleware('perm:city.delete');

    Route::get('store-categories', [StoreCategoryController::class, 'index'])->middleware('perm:store_category.view');
    Route::post('store-categories', [StoreCategoryController::class, 'store'])->middleware('perm:store_category.create');
    Route::put('store-categories/{store_category}', [StoreCategoryController::class, 'update'])->middleware('perm:store_category.update');
    Route::delete('store-categories/{store_category}', [StoreCategoryController::class, 'destroy'])->middleware('perm:store_category.delete');

    Route::get('services', [ServiceController::class, 'index'])->middleware('perm:service.view');
    Route::post('services', [ServiceController::class, 'store'])->middleware('perm:service.create');
    Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('perm:service.update');
    Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('perm:service.delete');

    Route::get('banners', [BannerController::class, 'index'])->middleware('perm:banner.view');
    Route::post('banners', [BannerController::class, 'store'])->middleware('perm:banner.create');
    Route::put('banners/{banner}', [BannerController::class, 'update'])->middleware('perm:banner.update');
    Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->middleware('perm:banner.delete');

    Route::get('articles', [ArticleController::class, 'index'])->middleware('perm:article.view');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->middleware('perm:article.view');
    Route::post('articles', [ArticleController::class, 'store'])->middleware('perm:article.create');
    Route::put('articles/{article}', [ArticleController::class, 'update'])->middleware('perm:article.update');
    Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->middleware('perm:article.delete');

    // ── notifications ─────────────────────────────────────────────────────
    Route::get('notifications', [NotificationController::class, 'index'])->middleware('perm:notification.view');
    Route::get('notifications/audience-size', [NotificationController::class, 'audienceSize'])->middleware('perm:notification.view');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->middleware('perm:notification.view');
    Route::post('notifications', [NotificationController::class, 'store'])->middleware('perm:notification.create');
    Route::post('notifications/{notification}/send', [NotificationController::class, 'send'])->middleware('perm:notification.create');
    Route::put('notifications/{notification}', [NotificationController::class, 'update'])->middleware('perm:notification.update');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->middleware('perm:notification.delete');

    // ── settings & audit trail ────────────────────────────────────────────
    Route::put('settings', [SettingsController::class, 'update'])->middleware('perm:settings.update');
    Route::get('activity', [ActivityController::class, 'index'])->middleware('perm:settings.view');

    // ── trash ─────────────────────────────────────────────────────────────
    Route::get('trash/summary', [TrashController::class, 'summary'])->middleware('perm:trash.view');
    Route::get('trash/{type}', [TrashController::class, 'index'])->middleware('perm:trash.view');
    Route::post('trash/{type}/empty', [TrashController::class, 'empty'])->middleware('perm:trash.delete');
    Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])->middleware('perm:trash.update');
    Route::delete('trash/{type}/{id}', [TrashController::class, 'forceDelete'])->middleware('perm:trash.delete');
});
