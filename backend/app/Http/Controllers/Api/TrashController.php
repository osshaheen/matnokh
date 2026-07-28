<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Banner;
use App\Models\City;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PushNotification;
use App\Models\Service;
use App\Models\StoreCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One place to browse, restore and permanently remove soft-deleted rows
 * across every module (سلّة المحذوفات).
 */
class TrashController extends Controller
{
    use HandlesResourceQuery;

    /** url segment => [model, arabic label, columns shown as the row title/subtitle] */
    protected const TYPES = [
        'orders' => [Order::class, 'الطلبات', ['order_no', 'drop_address']],
        'drivers' => [Driver::class, 'السائقون', ['name', 'phone']],
        'merchants' => [Merchant::class, 'التجّار', ['store_name', 'phone']],
        'customers' => [Customer::class, 'الزبائن', ['name', 'phone']],
        'withdraws' => [Withdraw::class, 'السحوبات', ['amount', 'status']],
        'subscriptions' => [Subscription::class, 'الاشتراكات', ['id', 'status']],
        'subscription-plans' => [SubscriptionPlan::class, 'الباقات', ['name', 'price']],
        'cities' => [City::class, 'المدن', ['name', 'name_en']],
        'store-categories' => [StoreCategory::class, 'تصنيفات المتاجر', ['name', 'name_en']],
        'services' => [Service::class, 'الخدمات', ['name', 'base_price']],
        'banners' => [Banner::class, 'البانرات', ['title', 'position']],
        'articles' => [Article::class, 'المقالات', ['title', 'slug']],
        'notifications' => [PushNotification::class, 'الإشعارات', ['title', 'audience']],
    ];

    /** GET /api/trash/summary — how many deleted rows each module holds. */
    public function summary(): JsonResponse
    {
        $this->guardTrash();

        $rows = collect(self::TYPES)->map(fn ($meta, $type) => [
            'type' => $type,
            'label' => $meta[1],
            'count' => $meta[0]::onlyTrashed()->count(),
        ])->values();

        return response()->json(['data' => $rows, 'total' => $rows->sum('count')]);
    }

    /** GET /api/trash/{type} */
    public function index(Request $request, string $type): JsonResponse
    {
        $this->guardTrash();
        [$model, $label, $columns] = $this->meta($type);

        $query = $model::onlyTrashed();

        if (($term = trim((string) $request->query('search', ''))) !== '') {
            $query->where(function ($q) use ($columns, $term) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        $items = $query->orderByDesc('deleted_at')
            ->paginate(min(max((int) $request->query('per_page', 15), 1), 100))
            ->withQueryString();

        return response()->json([
            'data' => collect($items->items())->map(fn (Model $row) => [
                'id' => $row->getKey(),
                'title' => (string) ($row->{$columns[0]} ?? '#'.$row->getKey()),
                'subtitle' => (string) ($row->{$columns[1]} ?? ''),
                'deleted_at' => $row->deleted_at,
            ]),
            'meta' => [
                'type' => $type,
                'label' => $label,
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /** POST /api/trash/{type}/{id}/restore */
    public function restore(string $type, int $id): JsonResponse
    {
        $this->guardRestore();
        [$model] = $this->meta($type);

        $model::onlyTrashed()->findOrFail($id)->restore();

        return response()->json(['message' => 'تمت الاستعادة بنجاح']);
    }

    /** DELETE /api/trash/{type}/{id} — permanent. */
    public function forceDelete(string $type, int $id): JsonResponse
    {
        $this->guardTrash();
        $this->guardDeletion();
        [$model] = $this->meta($type);

        $model::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(['message' => 'تم الحذف نهائياً']);
    }

    /** POST /api/trash/{type}/empty — permanently clears one module's trash. */
    public function empty(string $type): JsonResponse
    {
        $this->guardTrash();
        $this->guardDeletion();
        [$model] = $this->meta($type);

        $count = 0;
        $model::onlyTrashed()->chunkById(200, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                $row->forceDelete();
                $count++;
            }
        });

        return response()->json(['message' => "تم حذف {$count} عنصراً نهائياً", 'deleted' => $count]);
    }

    /** @return array{0:class-string<Model>,1:string,2:array<int,string>} */
    protected function meta(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404, 'نوع غير معروف');

        return self::TYPES[$type];
    }
}
