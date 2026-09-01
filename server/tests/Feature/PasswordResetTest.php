<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'testuser@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'testuser@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reset link sent to your email.']);

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_forgot_password_fails_for_invalid_email_format()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_returns_400_for_non_existent_email()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Unable to send reset link.']);
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password has been reset successfully.']);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Verify login works with new password
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
        ]);

        $loginResponse->assertStatus(200);
    }

    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Unable to reset password. Please try again.']);

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    public function test_reset_password_fails_when_passwords_do_not_match()
    {
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'resetme@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
