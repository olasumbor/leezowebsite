<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordChangeTest extends TestCase
{

    public function test_user_can_change_own_password_with_valid_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully.']);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/change-password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_admin_can_reset_any_user_password()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$targetUser->id}/password", [
                'new_password' => 'adminreset123',
                'new_password_confirmation' => 'adminreset123',
            ]);

        $response->assertStatus(200);

        $targetUser->refresh();
        $this->assertTrue(Hash::check('adminreset123', $targetUser->password));
    }

    public function test_regular_user_cannot_reset_another_user_password()
    {
        $user = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/admin/users/{$targetUser->id}/password", [
                'new_password' => 'hackedpassword123',
                'new_password_confirmation' => 'hackedpassword123',
            ]);

        $response->assertStatus(403);
    }
}
