<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use RefreshDatabase;

    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->city = City::create(['name' => 'جنين', 'delivery_fee' => 20]);
        $this->deleteJson("/api/cities/{$this->city->id}")->assertOk();
    }

    public function test_a_deleted_row_shows_up_in_its_bucket(): void
    {
        $this->getJson('/api/trash/cities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'جنين')
            ->assertJsonPath('meta.label', 'المدن');
    }

    public function test_the_summary_counts_every_module(): void
    {
        $response = $this->getJson('/api/trash/summary')->assertOk();

        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals(1, collect($response->json('data'))->firstWhere('type', 'cities')['count']);
        $this->assertEquals(0, collect($response->json('data'))->firstWhere('type', 'orders')['count']);
    }

    public function test_an_unknown_bucket_is_not_found(): void
    {
        $this->getJson('/api/trash/wharever')->assertStatus(404);
    }

    public function test_restoring_brings_the_row_back(): void
    {
        $this->postJson("/api/trash/cities/{$this->city->id}/restore")->assertOk();

        $this->assertNotSoftDeleted('cities', ['id' => $this->city->id]);
        $this->getJson('/api/trash/cities')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_force_delete_is_permanent(): void
    {
        $this->deleteJson("/api/trash/cities/{$this->city->id}")->assertOk();

        $this->assertDatabaseMissing('cities', ['id' => $this->city->id]);
    }

    public function test_emptying_a_bucket_clears_it(): void
    {
        City::create(['name' => 'طوباس'])->delete();

        $this->postJson('/api/trash/cities/empty')
            ->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertDatabaseCount('cities', 0);
    }

    public function test_the_trash_switch_closes_the_whole_section(): void
    {
        Setting::put('trash_enabled', false);

        $this->getJson('/api/trash/summary')->assertStatus(403);
        $this->getJson('/api/trash/cities')->assertStatus(403);
        $this->postJson("/api/trash/cities/{$this->city->id}/restore")->assertStatus(403);
    }

    public function test_restore_can_be_switched_off_on_its_own(): void
    {
        Setting::put('restore_enabled', false);

        $this->getJson('/api/trash/cities')->assertOk();
        $this->postJson("/api/trash/cities/{$this->city->id}/restore")->assertStatus(403);
    }
}
