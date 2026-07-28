<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Banner;
use App\Models\City;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Service;
use App\Models\StoreCategory;
use App\Models\StoreCategory as Category;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Withdraw;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Reference data (cities, categories, services, plans) plus a deterministic
 * demo dataset so the dashboard renders with real numbers on a fresh install.
 *
 * Safe to re-run: reference rows use firstOrCreate and the demo records are
 * skipped once any order exists.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->reference();

        if (Order::withTrashed()->exists()) {
            $this->command?->info('Demo records already present — skipping.');

            return;
        }

        $this->partners();
        $this->orders();
        $this->finance();
        $this->content();
    }

    protected function reference(): void
    {
        $cities = [
            ['رام الله', 'Ramallah', 15], ['نابلس', 'Nablus', 15], ['الخليل', 'Hebron', 20],
            ['بيت لحم', 'Bethlehem', 15], ['جنين', 'Jenin', 20], ['طولكرم', 'Tulkarm', 18],
            ['قلقيلية', 'Qalqilya', 18], ['أريحا', 'Jericho', 25], ['سلفيت', 'Salfit', 20],
            ['طوباس', 'Tubas', 22], ['غزة', 'Gaza', 15], ['القدس', 'Jerusalem', 25],
        ];
        foreach ($cities as $i => [$name, $en, $fee]) {
            City::firstOrCreate(['name' => $name], [
                'name_en' => $en, 'delivery_fee' => $fee, 'is_active' => true, 'sort' => $i,
            ]);
        }

        $categories = [
            ['مطاعم', 'Restaurants', '🍽️'], ['سوبرماركت', 'Supermarket', '🛒'],
            ['صيدليات', 'Pharmacies', '💊'], ['حلويات', 'Sweets', '🍰'],
            ['ملابس', 'Clothing', '👕'], ['إلكترونيات', 'Electronics', '📱'],
            ['ورود وهدايا', 'Gifts', '💐'], ['قهوة ومشروبات', 'Coffee', '☕'],
        ];
        foreach ($categories as $i => [$name, $en, $icon]) {
            Category::firstOrCreate(['name' => $name], [
                'name_en' => $en, 'icon' => $icon, 'is_active' => true, 'sort' => $i,
            ]);
        }

        $services = [
            ['توصيل طلبات', 'توصيل الطلبات من المتاجر إلى الزبائن', '📦', 15],
            ['شحن طرود', 'شحن الطرود بين المدن', '🚚', 25],
            ['توصيل مستندات', 'نقل المستندات والأوراق الرسمية', '📄', 12],
            ['تسوّق نيابة عنك', 'يقوم السائق بالشراء نيابة عن الزبون', '🛍️', 20],
        ];
        foreach ($services as $i => [$name, $desc, $icon, $price]) {
            Service::firstOrCreate(['name' => $name], [
                'description' => $desc, 'icon' => $icon, 'base_price' => $price,
                'is_active' => true, 'sort' => $i,
            ]);
        }

        $plans = [
            ['الباقة الأساسية', 99, 30, 8, ['حتى 100 طلب شهرياً', 'دعم فني عبر البريد', 'تقارير أساسية']],
            ['الباقة المتقدمة', 199, 30, 6, ['طلبات غير محدودة', 'دعم فني على مدار الساعة', 'تقارير متقدمة', 'أولوية في التوصيل']],
            ['الباقة الذهبية', 499, 90, 4, ['كل مزايا المتقدمة', 'مدير حساب مخصّص', 'بانر إعلاني في التطبيق', 'أقل نسبة عمولة']],
        ];
        foreach ($plans as $i => [$name, $price, $days, $rate, $features]) {
            SubscriptionPlan::firstOrCreate(['name' => $name], [
                'price' => $price, 'duration_days' => $days, 'commission_rate' => $rate,
                'features' => $features, 'is_active' => true, 'sort' => $i,
            ]);
        }
    }

    protected function partners(): void
    {
        $cityIds = City::pluck('id')->all();
        $categoryIds = StoreCategory::pluck('id')->all();

        $stores = [
            'مطعم الشام', 'سوبرماركت البركة', 'صيدلية النور', 'حلويات أبو السعود',
            'بوتيك الأناقة', 'موبايل زون', 'زهور فلسطين', 'كافيه الركن',
            'مطعم البيت الدمشقي', 'ماركت العائلة', 'صيدلية الحياة', 'حلويات القدس',
        ];
        foreach ($stores as $i => $name) {
            Merchant::create([
                'store_name' => $name,
                'owner_name' => 'صاحب '.$name,
                'phone' => '0599'.str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT),
                'email' => 'store'.($i + 1).'@wassilha.ps',
                'city_id' => $cityIds[$i % count($cityIds)],
                'store_category_id' => $categoryIds[$i % count($categoryIds)],
                'address' => 'شارع رئيسي - '.City::find($cityIds[$i % count($cityIds)])->name,
                'commission_rate' => [8, 10, 12][$i % 3],
                'balance' => 0,
                'status' => $i < 9 ? 'approved' : ($i < 11 ? 'pending' : 'suspended'),
                'is_active' => $i < 9,
            ]);
        }

        $driverNames = [
            'أحمد محمود', 'خالد يوسف', 'محمد سعيد', 'عمر حسن', 'يوسف إبراهيم',
            'سامي عبد الله', 'باسل نمر', 'مراد صالح', 'رامي زياد', 'نضال فارس',
        ];
        foreach ($driverNames as $i => $name) {
            Driver::create([
                'name' => $name,
                'phone' => '0568'.str_pad((string) (200000 + $i), 6, '0', STR_PAD_LEFT),
                'city_id' => $cityIds[$i % count($cityIds)],
                'vehicle_type' => ['motorcycle', 'car', 'motorcycle', 'van'][$i % 4],
                'vehicle_plate' => '1'.str_pad((string) (2345 + $i), 4, '0', STR_PAD_LEFT).'-5',
                'national_id' => '4'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                'status' => $i < 8 ? 'approved' : 'pending',
                'is_available' => $i < 5,
                'balance' => 0,
                'rating' => round(4 + ($i % 10) / 10, 2),
            ]);
        }

        $customerNames = [
            'ليلى أحمد', 'سارة خالد', 'نور محمد', 'دينا سامي', 'هبة عمر',
            'مالك زياد', 'طارق وليد', 'إياد ناصر', 'رنا فؤاد', 'مي جمال',
            'حسام رائد', 'آية منير', 'زياد عادل', 'لينا بشار', 'كرم أنور',
        ];
        foreach ($customerNames as $i => $name) {
            Customer::create([
                'name' => $name,
                'phone' => '0592'.str_pad((string) (300000 + $i), 6, '0', STR_PAD_LEFT),
                'city_id' => $cityIds[$i % count($cityIds)],
                'address' => 'حي '.['الطيرة', 'الماصيون', 'البالوع', 'عين مصباح'][$i % 4],
                'is_active' => true,
            ]);
        }
    }

    protected function orders(): void
    {
        $merchants = Merchant::where('status', 'approved')->get();
        $drivers = Driver::where('status', 'approved')->get();
        $customers = Customer::all();
        $services = Service::all();

        // 90 orders spread over the past two weeks, weighted towards delivered
        $mix = ['delivered', 'delivered', 'delivered', 'delivered', 'on_the_way', 'picked_up', 'accepted', 'pending', 'canceled'];

        for ($i = 0; $i < 90; $i++) {
            $createdAt = Carbon::today()->subDays($i % 14)->addHours(8 + ($i % 12))->addMinutes(($i * 7) % 60);
            $status = $mix[$i % count($mix)];
            $merchant = $merchants[$i % $merchants->count()];
            $customer = $customers[$i % $customers->count()];
            $service = $services[$i % $services->count()];
            $needsDriver = $status !== 'pending';
            $driver = $needsDriver ? $drivers[$i % $drivers->count()] : null;

            $items = 40 + (($i * 13) % 260);
            $fee = (float) ($merchant->city?->delivery_fee ?? 15);
            $commission = round($items * (float) $merchant->commission_rate / 100, 2);

            $order = new Order([
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'driver_id' => $driver?->id,
                'city_id' => $merchant->city_id,
                'service_id' => $service->id,
                'pickup_address' => $merchant->address,
                'drop_address' => $customer->address.' - '.($customer->city?->name ?? ''),
                'recipient_name' => $customer->name,
                'recipient_phone' => $customer->phone,
                'items_total' => $items,
                'delivery_fee' => $fee,
                'commission' => $commission,
                'payment_method' => ['cash', 'cash', 'card', 'wallet'][$i % 4],
                'is_paid' => $status === 'delivered',
                'status' => $status,
                'notes' => $i % 7 === 0 ? 'يُرجى الاتصال قبل الوصول' : null,
                'cancel_reason' => $status === 'canceled' ? 'الزبون ألغى الطلب' : null,
            ]);
            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;

            if (in_array($status, ['accepted', 'picked_up', 'on_the_way', 'delivered'], true)) {
                $order->accepted_at = $createdAt->copy()->addMinutes(5);
            }
            if (in_array($status, ['picked_up', 'on_the_way', 'delivered'], true)) {
                $order->picked_up_at = $createdAt->copy()->addMinutes(18);
            }
            if ($status === 'delivered') {
                $order->delivered_at = $createdAt->copy()->addMinutes(45);
            }
            if ($status === 'canceled') {
                $order->canceled_at = $createdAt->copy()->addMinutes(12);
            }
            $order->save();

            $order->statusLogs()->create([
                'status' => $status,
                'note' => 'بيانات تجريبية',
                'created_at' => $order->delivered_at ?? $createdAt,
                'updated_at' => $order->delivered_at ?? $createdAt,
            ]);

            // delivered orders pay out, matching what OrderController does live
            if ($status === 'delivered') {
                $merchant->increment('balance', max(0, $items - $commission));
                $driver?->increment('balance', $fee);
            }
        }
    }

    protected function finance(): void
    {
        $plans = SubscriptionPlan::all();
        foreach (Merchant::where('status', 'approved')->take(7)->get() as $i => $merchant) {
            $plan = $plans[$i % $plans->count()];
            $starts = Carbon::today()->subDays(20 - $i * 2);
            Subscription::create([
                'merchant_id' => $merchant->id,
                'subscription_plan_id' => $plan->id,
                'price' => $plan->price,
                'starts_at' => $starts->toDateString(),
                'ends_at' => $starts->copy()->addDays($plan->duration_days)->toDateString(),
                'status' => 'active',
            ]);
        }

        foreach (Driver::where('balance', '>', 150)->take(4)->get() as $i => $driver) {
            Withdraw::create([
                'requester_type' => Driver::class,
                'requester_id' => $driver->id,
                'amount' => 150,
                'method' => 'bank',
                'account_name' => $driver->name,
                'account_number' => 'PS'.str_pad((string) (9000 + $i), 8, '0', STR_PAD_LEFT),
                'bank_name' => 'بنك فلسطين',
                'status' => ['pending', 'pending', 'approved', 'paid'][$i % 4],
            ]);
        }

        foreach (Merchant::where('balance', '>', 300)->take(3)->get() as $i => $merchant) {
            Withdraw::create([
                'requester_type' => Merchant::class,
                'requester_id' => $merchant->id,
                'amount' => 300,
                'method' => 'bank',
                'account_name' => $merchant->owner_name,
                'account_number' => 'PS'.str_pad((string) (7000 + $i), 8, '0', STR_PAD_LEFT),
                'bank_name' => 'البنك العربي',
                'status' => $i === 0 ? 'pending' : 'approved',
            ]);
        }
    }

    protected function content(): void
    {
        $banners = [
            ['خصم 20% على أول طلب', 'home_top', 'all'],
            ['انضم إلينا كسائق', 'home_middle', 'drivers'],
            ['سجّل متجرك مجاناً', 'offers', 'merchants'],
        ];
        foreach ($banners as $i => [$title, $position, $audience]) {
            Banner::create([
                'title' => $title, 'position' => $position, 'audience' => $audience,
                'is_active' => true, 'sort' => $i,
            ]);
        }

        $articles = [
            ['كيف تطلب من وصلها؟', 'دليل سريع لإتمام طلبك خلال دقائق.', true],
            ['شروط الانضمام كسائق', 'المتطلبات والأوراق اللازمة للانضمام لفريق السائقين.', true],
            ['سياسة الخصوصية', 'كيف نتعامل مع بياناتك ونحافظ على خصوصيتك.', true],
            ['الشروط والأحكام', 'الشروط المنظّمة لاستخدام منصّة وصلها.', false],
        ];
        foreach ($articles as [$title, $excerpt, $published]) {
            Article::create([
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $excerpt.' '.str_repeat('هذا نص تجريبي يُستبدل بالمحتوى الحقيقي لاحقاً. ', 6),
                'is_published' => $published,
            ]);
        }
    }
}
