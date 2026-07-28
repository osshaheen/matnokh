<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_with_abilities(): void
    {
        $this->seed(AdminSeeder::class);
        User::where('email', AdminSeeder::EMAIL)->update(['password' => bcrypt('secret-password')]);

        $response = $this->postJson('/api/login', [
            'email' => AdminSeeder::EMAIL,
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles', 'abilities']]);

        $this->assertContains('order.view', $response->json('user.abilities'));
        $this->assertContains('admin', $response->json('user.roles'));
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->seed(AdminSeeder::class);

        $this->postJson('/api/login', [
            'email' => AdminSeeder::EMAIL,
            'password' => 'not-the-password',
        ])->assertStatus(422);
    }

    public function test_login_rejects_an_inactive_account(): void
    {
        $this->seed(AdminSeeder::class);
        User::where('email', AdminSeeder::EMAIL)
            ->update(['password' => bcrypt('secret-password'), 'is_active' => false]);

        $this->postJson('/api/login', [
            'email' => AdminSeeder::EMAIL,
            'password' => 'secret-password',
        ])->assertStatus(403);
    }

    public function test_guests_cannot_reach_the_api(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/logout')->assertOk();

        // the guard caches its user for the lifetime of a request; tests reuse
        // one application, so drop it to make the next call a genuine new request
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_a_route_is_refused_without_its_ability(): void
    {
        $this->actingAsUserWith(['order.view']);

        $this->getJson('/api/orders')->assertOk();
        $this->getJson('/api/drivers')->assertStatus(403);
        $this->postJson('/api/orders', ['drop_address' => 'رام الله'])->assertStatus(403);
    }
}
