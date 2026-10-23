<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_user_can_login_and_is_redirected_to_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_required_and_format_messages(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);

        $this->post('/login', ['email' => 'invalid', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_wrong_credentials_message(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_to_five_attempts_per_minute(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_authenticated_user_is_redirected_from_login_page_to_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/admin');
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
