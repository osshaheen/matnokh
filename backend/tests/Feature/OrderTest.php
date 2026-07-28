<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected City $city;
    protected Merchant $merchant;
    protected Driver $driver;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->city = City::create(['name' => 'رام الله', 'delivery_fee' => 15]);
        $this->merchant = Merchant::create([
            'store_name' => 'مطعم الشام', 'phone' => '0599111111',
            'city_id' => $this->city->id, 'commission_rate' => 10,
            'status' => 'approved', 'is_active' => true,
        ]);
        $this->driver = Driver::create([
            'name' => 'أحمد', 'phone' => '0568111111',
            'city_id' => $this->city->id, 'status' => 'approved', 'is_available' => true,
        ]);
        $this->customer = Customer::create(['name' => 'ليلى', 'phone' => '0592111111']);
    }

    protected function createOrder(array $overrides = []): Order
    {
        $response = $this->postJson('/api/orders', array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'city_id' => $this->city->id,
            'drop_address' => 'شارع الإرسال',
            'items_total' => 100,
            'delivery_fee' => 15,
        ], $overrides));

        $response->assertCreated();

        return Order::findOrFail($response->json('data.id'));
    }

    public function test_creating_an_order_numbers_it_and_computes_the_total(): void
    {
        $order = $this->createOrder(['discount' => 5]);

        $this->assertStringStartsWith('WS-', $order->order_no);
        $this->assertEquals(110.0, (float) $order->total, 'total = items + fee - discount');
        $this->assertEquals('pending', $order->status);
    }

    public function test_commission_defaults_to_the_merchant_rate(): void
    {
        $order = $this->createOrder();

        $this->assertEquals(10.0, (float) $order->commission, '10% of 100');
    }

    public function test_order_numbers_stay_unique_across_several_orders(): void
    {
        $numbers = collect(range(1, 3))->map(fn () => $this->createOrder()->order_no);

        $this->assertCount(3, $numbers->unique(), 'each order gets its own number');
    }

    public function test_orders_can_be_switched_off_from_settings(): void
    {
        Setting::put('orders_enabled', false);

        $this->postJson('/api/orders', [
            'drop_address' => 'شارع الإرسال',
        ])->assertStatus(422);
    }

    public function test_a_status_cannot_move_forward_without_a_driver(): void
    {
        $order = $this->createOrder();

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'accepted'])
            ->assertStatus(422);
    }

    public function test_assigning_a_driver_accepts_a_pending_order_and_logs_it(): void
    {
        $order = $this->createOrder();

        $this->patchJson("/api/orders/{$order->id}/assign", ['driver_id' => $this->driver->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.driver.id', $this->driver->id);

        $this->assertTrue($order->fresh()->statusLogs()->where('note', 'like', '%أحمد%')->exists());
    }

    public function test_an_unapproved_driver_cannot_be_assigned(): void
    {
        $order = $this->createOrder();
        $this->driver->update(['status' => 'suspended']);

        $this->patchJson("/api/orders/{$order->id}/assign", ['driver_id' => $this->driver->id])
            ->assertStatus(422);
    }

    public function test_delivering_stamps_the_time_and_pays_both_parties(): void
    {
        $order = $this->createOrder();
        $this->patchJson("/api/orders/{$order->id}/assign", ['driver_id' => $this->driver->id])->assertOk();

        foreach (['picked_up', 'on_the_way', 'delivered'] as $status) {
            $this->patchJson("/api/orders/{$order->id}/status", ['status' => $status])->assertOk();
        }

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);

        // merchant keeps the goods minus commission, driver keeps the fee
        $this->assertEquals(90.0, (float) $this->merchant->fresh()->balance);
        $this->assertEquals(15.0, (float) $this->driver->fresh()->balance);
    }

    public function test_a_finished_order_cannot_change_status_again(): void
    {
        $order = $this->createOrder();
        $this->patchJson("/api/orders/{$order->id}/assign", ['driver_id' => $this->driver->id])->assertOk();
        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'canceled'])->assertOk();

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertStatus(422);
    }

    public function test_the_list_filters_by_status_and_searches(): void
    {
        $delivered = $this->createOrder(['drop_address' => 'نابلس الشمالية']);
        $this->createOrder(['drop_address' => 'الخليل']);
        $delivered->update(['status' => 'delivered']);

        $this->getJson('/api/orders?status=delivered')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $delivered->id);

        $this->getJson('/api/orders?search=الخليل')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_includes_the_timeline(): void
    {
        $order = $this->createOrder();

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order_no', $order->order_no)
            ->assertJsonCount(1, 'data.timeline');
    }

    public function test_deleting_respects_the_platform_switch(): void
    {
        $order = $this->createOrder();

        Setting::put('deletion_enabled', false);
        $this->deleteJson("/api/orders/{$order->id}")->assertStatus(403);

        Setting::put('deletion_enabled', true);
        $this->deleteJson("/api/orders/{$order->id}")->assertOk();
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }
}
