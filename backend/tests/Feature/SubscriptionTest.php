<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Merchant $merchant;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->merchant = Merchant::create([
            'store_name' => 'متجر', 'phone' => '0599444444', 'status' => 'approved',
        ]);
        $this->plan = SubscriptionPlan::create([
            'name' => 'الأساسية', 'price' => 99, 'duration_days' => 30, 'is_active' => true,
        ]);
    }

    protected function subscribe(): Subscription
    {
        $response = $this->postJson('/api/subscriptions', [
            'merchant_id' => $this->merchant->id,
            'subscription_plan_id' => $this->plan->id,
        ])->assertCreated();

        return Subscription::findOrFail($response->json('data.id'));
    }

    public function test_subscribing_derives_the_dates_and_price_from_the_plan(): void
    {
        $subscription = $this->subscribe();

        $this->assertEquals(99.0, (float) $subscription->price);
        $this->assertEquals(today()->toDateString(), $subscription->starts_at->toDateString());
        $this->assertEquals(today()->addDays(30)->toDateString(), $subscription->ends_at->toDateString());
        $this->assertEquals('active', $subscription->status);
    }

    public function test_a_new_subscription_supersedes_the_active_one(): void
    {
        $first = $this->subscribe();
        $second = $this->subscribe();

        $this->assertEquals('canceled', $first->fresh()->status);
        $this->assertEquals('active', $second->fresh()->status);
        $this->assertEquals(1, Subscription::where('merchant_id', $this->merchant->id)
            ->where('status', 'active')->count());
    }

    public function test_renewing_extends_from_the_current_end_date(): void
    {
        $subscription = $this->subscribe();

        $response = $this->postJson("/api/subscriptions/{$subscription->id}/renew")->assertCreated();

        $this->assertEquals('expired', $subscription->fresh()->status);
        $this->assertEquals(
            $subscription->ends_at->toDateString(),
            $response->json('data.starts_at'),
            'the renewal picks up where the old one ends'
        );
    }

    public function test_a_plan_with_active_subscriptions_cannot_be_deleted(): void
    {
        $this->subscribe();

        $this->deleteJson("/api/subscription-plans/{$this->plan->id}")->assertStatus(422);
    }

    public function test_expiring_filter_finds_subscriptions_about_to_end(): void
    {
        $subscription = $this->subscribe();

        $this->getJson('/api/subscriptions?expiring=7')->assertOk()->assertJsonCount(0, 'data');

        $subscription->update(['ends_at' => today()->addDays(3)]);
        $this->getJson('/api/subscriptions?expiring=7')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_the_merchant_list_carries_its_active_subscription(): void
    {
        $this->subscribe();

        $this->getJson('/api/merchants')
            ->assertOk()
            ->assertJsonPath('data.0.subscription.plan', 'الأساسية');
    }
}
