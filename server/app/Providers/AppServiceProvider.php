<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:5500');
            if (str_contains($frontendUrl, 'reset-password.html')) {
                return $frontendUrl . '?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
            }
            return rtrim($frontendUrl, '/') . '/reset-password.html?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:5500');
            if (str_contains($frontendUrl, 'reset-password.html')) {
                $url = $frontendUrl . '?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
            } else {
                $url = rtrim($frontendUrl, '/') . '/reset-password.html?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
            }

            $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('Reset Your Password - Leezofood NG.Export')
                ->view('emails.password_reset', [
                    'user' => $notifiable,
                    'url' => $url,
                    'count' => $count,
                ]);
        });
    }
}


