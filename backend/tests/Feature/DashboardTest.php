<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_the_dashboard_reports_todays_numbers(): void
    {
        $city = City::create(['name' => 'بيت لحم', 'delivery_fee' => 15]);
        $merchant = Merchant::create([
            'store_name' => 'ماركت', 'phone' => '0599333333',
            'city_id' => $city->id, 'status' => 'approved', 'is_active' => true,
        ]);
        Driver::create(['name' => 'سامي', 'phone' => '0568333333', 'status' => 'approved', 'is_available' => true]);

        Order::create([
            'merchant_id' => $merchant->id, 'city_id' => $city->id, 'drop_address' => 'أ',
            'items_total' => 100, 'delivery_fee' => 15, 'commission' => 10,
            'status' => 'delivered', 'delivered_at' => now(),
        ]);
        Order::create([
            'merchant_id' => $merchant->id, 'city_id' => $city->id, 'drop_address' => 'ب',
            'items_total' => 50, 'delivery_fee' => 15, 'status' => 'pending',
        ]);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertEquals(2, $response->json('data.kpis.orders_today'));
        $this->assertEquals(1, $response->json('data.kpis.orders_active'));
        $this->assertEquals(115.0, $response->json('data.kpis.revenue_today'));
        $this->assertEquals(1, $response->json('data.kpis.drivers_available'));
        $this->assertEquals(1, $response->json('data.kpis.merchants_active'));
    }

    public function test_the_status_breakdown_is_zero_filled(): void
    {
        $breakdown = $this->getJson('/api/dashboard')->assertOk()->json('data.orders_by_status');

        foreach (Order::STATUSES as $status) {
            $this->assertArrayHasKey($status, $breakdown, "$status must be present even at zero");
        }
    }

    public function test_the_chart_covers_every_requested_day(): void
    {
        $chart = $this->getJson('/api/dashboard?days=7')->assertOk()->json('data.orders_chart');

        $this->assertCount(7, $chart);
        $this->assertEquals(today()->toDateString(), end($chart)['day'], 'the newest point is today');
    }

    public function test_lookups_only_offer_usable_options(): void
    {
        City::create(['name' => 'مدينة مفعّلة', 'is_active' => true]);
        City::create(['name' => 'مدينة معطّلة', 'is_active' => false]);
        Driver::create(['name' => 'معتمد', 'phone' => '0568444444', 'status' => 'approved']);
        Driver::create(['name' => 'قيد المراجعة', 'phone' => '0568555555', 'status' => 'pending']);

        $response = $this->getJson('/api/lookups')->assertOk();

        $this->assertCount(1, $response->json('data.cities'));
        $this->assertCount(1, $response->json('data.drivers'));
        $this->assertEquals(Order::STATUSES, $response->json('data.enums.order_status'));
    }

    public function test_settings_round_trip_through_the_api(): void
    {
        $this->putJson('/api/settings', [
            'app_name' => 'وصلها',
            'default_delivery_fee' => 20,
            'deletion_enabled' => false,
        ])->assertOk()->assertJsonPath('data.deletion_enabled', false);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.default_delivery_fee', 20)
            ->assertJsonPath('data.deletion_enabled', false)
            ->assertJsonPath('data.trash_enabled', true);
    }
}
