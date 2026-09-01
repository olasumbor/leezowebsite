<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5500');
        if (str_contains($frontendUrl, 'reset-password.html')) {
            $url = $frontendUrl . '?token=' . $token . '&email=' . urlencode($this->email);
        } else {
            $url = rtrim($frontendUrl, '/') . '/client/reset-password.html?token=' . $token . '&email=' . urlencode($this->email);
        }
        
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($url));
    }
}
