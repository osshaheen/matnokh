<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Setting;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase;

    protected Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->driver = Driver::create([
            'name' => 'خالد', 'phone' => '0568222222',
            'status' => 'approved', 'balance' => 500,
        ]);
    }

    protected function request(array $overrides = []): Withdraw
    {
        $response = $this->postJson('/api/withdraws', array_merge([
            'requester_type' => 'driver',
            'requester_id' => $this->driver->id,
            'amount' => 200,
            'method' => 'bank',
        ], $overrides));

        $response->assertCreated();

        return Withdraw::findOrFail($response->json('data.id'));
    }

    public function test_a_request_is_created_as_pending_against_the_driver(): void
    {
        $withdraw = $this->request();

        $this->assertEquals('pending', $withdraw->status);
        $this->assertEquals(Driver::class, $withdraw->requester_type);
        $this->assertEquals('driver', $withdraw->requester_type_key);
    }

    public function test_the_platform_minimum_is_enforced(): void
    {
        Setting::put('min_withdraw_amount', 300);

        $this->postJson('/api/withdraws', [
            'requester_type' => 'driver',
            'requester_id' => $this->driver->id,
            'amount' => 100,
        ])->assertStatus(422);
    }

    public function test_a_request_cannot_exceed_the_balance(): void
    {
        $this->postJson('/api/withdraws', [
            'requester_type' => 'driver',
            'requester_id' => $this->driver->id,
            'amount' => 900,
        ])->assertStatus(422);
    }

    public function test_approving_does_not_move_money_but_paying_does(): void
    {
        $withdraw = $this->request();

        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'approved'])->assertOk();
        $this->assertEquals(500.0, (float) $this->driver->fresh()->balance, 'approval alone must not debit');

        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'paid'])->assertOk();
        $this->assertEquals(300.0, (float) $this->driver->fresh()->balance, 'payout debits once');

        $this->assertNotNull($withdraw->fresh()->processed_at);
    }

    public function test_illegal_transitions_are_refused(): void
    {
        $withdraw = $this->request();

        // pending → paid skips approval
        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'paid'])
            ->assertStatus(422);

        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'rejected'])->assertOk();
        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'approved'])
            ->assertStatus(422);
    }

    public function test_a_paid_request_cannot_be_deleted(): void
    {
        $withdraw = $this->request();
        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'approved'])->assertOk();
        $this->patchJson("/api/withdraws/{$withdraw->id}/status", ['status' => 'paid'])->assertOk();

        $this->deleteJson("/api/withdraws/{$withdraw->id}")->assertStatus(422);
    }

    public function test_the_list_filters_by_requester_type(): void
    {
        $this->request();

        $this->getJson('/api/withdraws?requester_type=driver')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/withdraws?requester_type=merchant')
            ->assertOk()->assertJsonCount(0, 'data');
    }
}
