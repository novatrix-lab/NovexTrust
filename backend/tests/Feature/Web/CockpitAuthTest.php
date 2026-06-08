<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CockpitAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_to_the_cockpit(): void
    {
        $user = User::factory()->create(['email' => 'owner@t.test', 'password' => Hash::make('secret-pass')]);

        $this->post('/login', ['email' => 'owner@t.test', 'password' => 'secret-pass'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'owner@t.test', 'password' => Hash::make('secret-pass')]);

        $this->from('/login')
            ->post('/login', ['email' => 'owner@t.test', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
