<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_is_displayed(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertViewIs('auth.register')
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_user_is_registered_and_redirected_to_admin(): void
    {
        $response = $this->post('/register', [
            'name' => '管理 太郎',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertSame('管理 太郎', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_required_fields_messages(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors([
                'name' => 'お名前を入力してください',
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);
        $this->assertGuest();
    }

    public function test_invalid_email_short_password_and_mismatch_messages(): void
    {
        $this->post('/register', [
            'name' => '管理 太郎',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->post('/register', [
            'name' => '管理 太郎',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/register', [
            'name' => '管理 太郎',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }
}
