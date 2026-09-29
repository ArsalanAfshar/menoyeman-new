<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Menu;
use App\Models\OtpCode;
use App\Models\User;
use App\Support\Persian;
use Tests\Concerns\RefreshesDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_login_page_is_persian_and_rtl(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('ورود به منوی من');
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="fa"', false);
        $response->assertDontSee('>Submit<', false);
    }

    public function test_otp_can_be_requested_and_session_receives_code_in_fake_mode(): void
    {
        config(['sms.driver' => 'log', 'sms.fake_display' => true]);

        $response = $this->post('/login/otp', ['phone' => '09123456789']);

        $response->assertRedirect('/login/verify');
        $this->assertDatabaseHas('otp_codes', ['phone' => '09123456789']);
        $this->assertNotNull(session('phone'));
        $this->assertNotNull(session('otp_fake_code'));
    }

    public function test_persian_digits_in_phone_are_normalized(): void
    {
        config(['sms.driver' => 'log', 'sms.fake_display' => true]);

        $response = $this->post('/login/otp', ['phone' => '۰۹۱۲۳۴۵۶۷۸۹']);

        $response->assertRedirect('/login/verify');
        $this->assertDatabaseHas('otp_codes', ['phone' => '09123456789']);
    }

    public function test_international_format_is_normalized(): void
    {
        config(['sms.driver' => 'log', 'sms.fake_display' => true]);

        $this->post('/login/otp', ['phone' => '+98 912 345 6789'])->assertRedirect('/login/verify');
        $this->assertDatabaseHas('otp_codes', ['phone' => '09123456789']);
    }

    public function test_invalid_phone_is_rejected_with_persian_message(): void
    {
        $response = $this->post('/login/otp', ['phone' => '12345']);

        $response->assertSessionHasErrors('phone');
        $this->assertStringContainsString('شماره موبایل', session('errors')->first('phone'));
    }

    public function test_correct_otp_logs_the_user_in(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $code = session('otp_fake_code');

        $response = $this->post('/login/verify', ['code' => $code]);

        $response->assertRedirect('/panel');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['phone' => '09123456789', 'role' => 'owner']);
    }

    public function test_wrong_otp_shows_persian_error_and_stays_on_page(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $wrong = str_pad('1', 6, '0', STR_PAD_LEFT);

        $response = $this->post('/login/verify', ['code' => $wrong]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_otp_is_rejected(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $code = session('otp_fake_code');

        // Expire every code in the DB.
        OtpCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->post('/login/verify', ['code' => $code]);
        $this->assertGuest();
    }

    public function test_too_many_wrong_attempts_invalidates_the_code(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $code = session('otp_fake_code');
        $wrong = '000000';

        foreach (range(1, 5) as $i) {
            $this->post('/login/verify', ['code' => $wrong]);
        }

        // Even the CORRECT code must now fail (attempts exhausted).
        $this->post('/login/verify', ['code' => $code]);
        $this->assertGuest();
    }

    public function test_honeypot_field_silently_drops_bots(): void
    {
        $response = $this->post('/login/otp', [
            'phone' => '09123456789',
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect('/login/verify');
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_resend_rate_limit_is_enforced(): void
    {
        config(['sms.driver' => 'log', 'sms.fake_display' => true]);

        $this->post('/login/otp', ['phone' => '09123456789']);
        $this->app['session']->forget('phone');
        $response = $this->post('/login/otp', ['phone' => '09123456789']);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_password_login_works_for_accounts_with_password(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'password' => Hash::make('secret-pass'),
        ]);

        $response = $this->post('/login/password', [
            'phone' => '09123456789',
            'password' => 'secret-pass',
        ]);

        $response->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'phone' => '09123456789',
            'password' => Hash::make('secret-pass'),
        ]);

        $response = $this->post('/login/password', [
            'phone' => '09123456789',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $code = session('otp_fake_code');
        User::factory()->inactive()->create(['phone' => '09123456789']);

        $this->post('/login/verify', ['code' => $code]);
        $this->assertGuest();
    }

    public function test_forgot_password_flow_sets_new_password(): void
    {
        User::factory()->create(['phone' => '09123456789']);

        $this->post('/password/forgot', ['phone' => '09123456789'])->assertRedirect('/login/verify');
        $code = session('otp_fake_code');

        $this->post('/login/verify', ['code' => $code])->assertRedirect('/password/reset');
        $this->post('/password/reset', [
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertRedirect('/panel');

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('new-secret-1', auth()->user()->password));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/panel')->assertRedirect('/login');
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('منوی من');
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
    }

    public function test_unknown_page_shows_persian_404(): void
    {
        $response = $this->get('/definitely-not-a-page');

        $response->assertNotFound();
        $response->assertSee('صفحه پیدا نشد');
    }

    public function test_logged_in_user_can_complete_onboarding(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $this->post('/login/verify', ['code' => session('otp_fake_code')]);

        $this->get('/panel/onboarding')->assertOk()->assertSee('بیایید منوی شما را بسازیم');

        $this->post('/panel/onboarding', [
            'name' => 'کافه الماس',
            'business_type' => 'cafe',
            'slug' => 'almas-cafe',
        ])->assertRedirect('/panel');

        $this->assertDatabaseHas('menus', [
            'user_id' => auth()->id(),
            'slug' => 'almas-cafe',
            'name' => 'کافه الماس',
            'business_type' => 'cafe',
            'status' => 'trial',
        ]);
    }

    public function test_slug_check_endpoint_reports_availability(): void
    {
        $this->post('/login/otp', ['phone' => '09123456789']);
        $this->post('/login/verify', ['code' => session('otp_fake_code')]);

        $this->postJson('/panel/onboarding/slug-check', ['slug' => 'almas-cafe'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson('/panel/onboarding/slug-check', ['slug' => 'admin'])
            ->assertOk()
            ->assertJson(['ok' => false, 'error' => 'reserved']);

        $this->post('/panel/onboarding', [
            'name' => 'کافه الماس',
            'business_type' => 'cafe',
            'slug' => 'almas-cafe',
        ])->assertRedirect('/panel');

        // A slug taken by ANOTHER owner is reported as taken.
        Menu::factory()->create(['slug' => 'another-cafe']);
        $this->postJson('/panel/onboarding/slug-check', ['slug' => 'another-cafe'])
            ->assertOk()
            ->assertJson(['ok' => false, 'error' => 'taken']);

        // The owner's OWN slug stays usable for their own menu.
        $this->postJson('/panel/onboarding/slug-check', ['slug' => 'almas-cafe'])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
