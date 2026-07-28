<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->city = City::create(['name' => 'الخليل', 'delivery_fee' => 20]);
    }

    public function test_a_driver_is_created_pending_and_unavailable(): void
    {
        $response = $this->postJson('/api/drivers', [
            'name' => 'يوسف', 'phone' => '0568666666', 'city_id' => $this->city->id,
        ])->assertCreated();

        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertFalse($response->json('data.is_available'));
    }

    public function test_phone_numbers_are_unique_per_module(): void
    {
        $this->postJson('/api/drivers', ['name' => 'يوسف', 'phone' => '0568666666'])->assertCreated();
        $this->postJson('/api/drivers', ['name' => 'آخر', 'phone' => '0568666666'])->assertStatus(422);

        // the same number is fine on a different module
        $this->postJson('/api/customers', ['name' => 'زبون', 'phone' => '0568666666'])->assertCreated();
    }

    public function test_an_unapproved_driver_cannot_be_made_available(): void
    {
        $driver = Driver::create(['name' => 'نضال', 'phone' => '0568777777', 'status' => 'pending']);

        $this->patchJson("/api/drivers/{$driver->id}/availability", ['is_available' => true])
            ->assertStatus(422);
    }

    public function test_dropping_approval_also_drops_the_driver_from_the_pool(): void
    {
        $driver = Driver::create([
            'name' => 'مراد', 'phone' => '0568888888', 'status' => 'approved', 'is_available' => true,
        ]);

        $this->patchJson("/api/drivers/{$driver->id}/status", ['status' => 'suspended'])->assertOk();

        $this->assertFalse($driver->fresh()->is_available);
    }

    public function test_a_driver_with_running_orders_cannot_be_deleted(): void
    {
        $driver = Driver::create(['name' => 'باسل', 'phone' => '0568999999', 'status' => 'approved']);
        $order = Order::create(['drop_address' => 'أ', 'driver_id' => $driver->id, 'status' => 'on_the_way']);

        $this->deleteJson("/api/drivers/{$driver->id}")->assertStatus(422);

        $order->update(['status' => 'delivered']);
        $this->deleteJson("/api/drivers/{$driver->id}")->assertOk();
    }

    public function test_approving_a_merchant_activates_it(): void
    {
        $merchant = Merchant::create([
            'store_name' => 'متجر جديد', 'phone' => '0599555555', 'status' => 'pending', 'is_active' => false,
        ]);

        $this->patchJson("/api/merchants/{$merchant->id}/status", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->patchJson("/api/merchants/{$merchant->id}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_the_driver_profile_reports_its_stats(): void
    {
        $driver = Driver::create(['name' => 'رامي', 'phone' => '0568101010', 'status' => 'approved']);
        Order::create(['drop_address' => 'أ', 'driver_id' => $driver->id, 'status' => 'delivered', 'delivery_fee' => 15]);
        Order::create(['drop_address' => 'ب', 'driver_id' => $driver->id, 'status' => 'delivered', 'delivery_fee' => 20]);
        Order::create(['drop_address' => 'ج', 'driver_id' => $driver->id, 'status' => 'on_the_way']);

        $response = $this->getJson("/api/drivers/{$driver->id}")->assertOk();

        $this->assertEquals(2, $response->json('stats.delivered'));
        $this->assertEquals(1, $response->json('stats.active'));
        $this->assertEquals(35.0, $response->json('stats.earnings'));
        $this->assertCount(3, $response->json('recent_orders'));
    }

    public function test_a_city_in_use_cannot_be_deleted(): void
    {
        Order::create(['drop_address' => 'أ', 'city_id' => $this->city->id]);

        $this->deleteJson("/api/cities/{$this->city->id}")->assertStatus(422);
    }

    public function test_articles_slug_themselves_and_hide_the_body_in_lists(): void
    {
        $this->postJson('/api/articles', [
            'title' => 'كيف تطلب من وصلها؟',
            'body' => 'نص المقال الكامل',
            'is_published' => true,
        ])->assertCreated();

        $list = $this->getJson('/api/articles')->assertOk();
        $this->assertArrayNotHasKey('body', $list->json('data.0'), 'lists stay light');
        $this->assertNotNull($list->json('data.0.slug'));
        $this->assertNotNull($list->json('data.0.published_at'), 'publishing stamps the date');

        $id = $list->json('data.0.id');
        $this->getJson("/api/articles/{$id}")
            ->assertOk()
            ->assertJsonPath('data.body', 'نص المقال الكامل');
    }

    public function test_a_notification_reaches_the_audience_it_names(): void
    {
        Driver::create(['name' => 'أ', 'phone' => '0568111222', 'status' => 'approved']);
        Driver::create(['name' => 'ب', 'phone' => '0568111333', 'status' => 'approved']);
        Driver::create(['name' => 'ج', 'phone' => '0568111444', 'status' => 'pending']);

        $this->getJson('/api/notifications/audience-size?audience=drivers')
            ->assertOk()
            ->assertJsonPath('data.size', 2);

        $response = $this->postJson('/api/notifications', [
            'title' => 'تحديث', 'body' => 'رسالة', 'audience' => 'drivers', 'send_now' => true,
        ])->assertCreated();

        $this->assertEquals('sent', $response->json('data.status'));
        $this->assertEquals(2, $response->json('data.sent_count'));

        // a sent notification is frozen
        $this->putJson("/api/notifications/{$response->json('data.id')}", ['title' => 'تعديل'])
            ->assertStatus(422);
    }
}
