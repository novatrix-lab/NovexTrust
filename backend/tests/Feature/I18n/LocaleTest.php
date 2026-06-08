<?php

declare(strict_types=1);

namespace Tests\Feature\I18n;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cockpit_renders_in_the_users_locale_with_rtl(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create(['locale' => 'ar']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('لوحة التحكم', false); // Arabic "Dashboard" nav label
    }

    public function test_default_locale_is_english_ltr(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Dashboard', false);
    }

    public function test_locale_switch_persists_for_the_user(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post('/locale', ['locale' => 'ar']);

        $this->assertSame('ar', $user->fresh()->locale);
    }
}
