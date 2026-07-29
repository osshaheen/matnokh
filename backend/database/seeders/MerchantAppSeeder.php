<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductImage;
use App\Models\ProductStock;
use App\Models\StoreSection;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MerchantAppSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('products')) {
            return; // migrations not applied yet
        }

        $merchant = Merchant::where('status', 'approved')->first();
        if (! $merchant) {
            return;
        }

        // 1) login account (phone + password) — for testing all login methods
        $user = $merchant->user ?: User::firstOrCreate(
            ['phone' => $merchant->phone],
            ['name' => $merchant->store_name, 'email' => $merchant->email, 'password' => Hash::make('merchant123'), 'is_active' => true]
        );
        if (! $user->hasRole('merchant')) {
            $user->assignRole('merchant');
        }
        $user->update(['password' => Hash::make('merchant123')]);
        $merchant->forceFill(['user_id' => $user->id, 'is_open' => true, 'rating' => 4.8])->save();

        if ($merchant->branches()->exists()) {
            return; // already seeded
        }

        // 2) branches
        $main = Branch::create(['merchant_id' => $merchant->id, 'name' => 'الفرع الرئيسي', 'city_id' => $merchant->city_id, 'phone' => $merchant->phone, 'hours' => '09:00 - 23:00', 'is_main' => true, 'lat' => 31.9, 'lng' => 35.2]);
        $b2 = Branch::create(['merchant_id' => $merchant->id, 'name' => 'فرع البلدة', 'city_id' => $merchant->city_id, 'phone' => '0599112233', 'hours' => '10:00 - 22:00', 'is_main' => false, 'lat' => 31.91, 'lng' => 35.21]);
        $branches = [$main, $b2];

        // 3) sections
        $secNames = [['وجبات رئيسية', '🍽️'], ['مشروبات', '🥤'], ['حلويات', '🍰']];
        $sections = [];
        foreach ($secNames as $i => [$n, $e]) {
            $sections[] = StoreSection::create(['merchant_id' => $merchant->id, 'name' => $n, 'icon' => $e, 'sort' => $i]);
        }

        // 4) products (+ images, addons, per-branch stock)
        $catalog = [
            ['شاورما دجاج', 25, null, 0, 'active'],
            ['شاورما لحمة', 32, 38, 0, 'active'],          // on offer
            ['بروست نصف', 40, null, 0, 'active'],
            ['عصير برتقال طازج', 12, null, 1, 'active'],
            ['كولا', 6, null, 1, 'active'],
            ['كنافة نابلسية', 20, 25, 2, 'draft'],
        ];
        foreach ($catalog as $i => [$name, $price, $before, $secIdx, $status]) {
            $p = Product::create([
                'merchant_id' => $merchant->id,
                'store_section_id' => $sections[$secIdx]->id,
                'name' => $name, 'price' => $price, 'price_before' => $before,
                'status' => $status, 'sort' => $i,
                'description' => 'وصف تجريبي للمنتج '.$name,
            ]);
            ProductImage::create(['product_id' => $p->id, 'url' => 'https://picsum.photos/seed/prod'.$p->id.'/400', 'sort' => 0]);
            if ($i % 2 === 0) {
                ProductAddon::create(['product_id' => $p->id, 'name' => 'إضافة جبنة', 'price' => 5]);
                ProductAddon::create(['product_id' => $p->id, 'name' => 'صلصة حارة', 'price' => 2]);
            }
            foreach ($branches as $bi => $branch) {
                ProductStock::create(['product_id' => $p->id, 'branch_id' => $branch->id, 'in_stock' => ! ($i === 5 && $bi === 1)]);
            }
        }

        // 5) wallet transactions (funding per delivered order + one withdrawal)
        $delivered = $merchant->orders()->where('status', 'delivered')->latest('id')->take(5)->get();
        foreach ($delivered as $o) {
            WalletTransaction::create(['merchant_id' => $merchant->id, 'type' => 'funding', 'amount' => $o->items_total, 'order_id' => $o->id, 'status' => 'done', 'note' => 'تمويل طلب '.$o->order_no]);
        }
        WalletTransaction::create(['merchant_id' => $merchant->id, 'type' => 'withdrawal', 'amount' => 300, 'status' => 'done', 'method' => 'bank', 'note' => 'سحب منفّذ — تحويل بنكي']);
    }
}
