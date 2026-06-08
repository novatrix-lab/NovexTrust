<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'owner@t.test', 'password' => Hash::make('secret-pass')]);

        // throttle:6,1 — the 7th attempt within the window is blocked.
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'owner@t.test', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'owner@t.test', 'password' => 'wrong'])
            ->assertStatus(429);
    }
}
