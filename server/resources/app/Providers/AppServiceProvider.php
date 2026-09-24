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
        // Defensive fallback: ensure the dompdf bindings exist even if the
        // package's service provider was skipped (e.g. stale
        // bootstrap/cache/services.php + packages.php on the production host
        // after a deploy that lacked `php artisan package:discover` or
        // `optimize:clear`). This is what produced
        // "Target class [dompdf.wrapper] does not exist." in production.
        // The bindings below mirror Barryvdh\DomPDF\ServiceProvider::register().
        // They are registered with bindIf() so normal auto-discovery /
        // explicit provider registration still wins and nothing breaks locally.
        $this->registerDompdfFallbackBindings();
    }

    /**
     * Register dompdf bindings if the package provider was skipped.
     *
     * Mirrors Barryvdh\DomPDF\ServiceProvider::register() but uses bindIf()
     * so a normally-discovered provider still takes precedence.
     */
    private function registerDompdfFallbackBindings(): void
    {
        if (! class_exists(\Dompdf\Dompdf::class) || ! class_exists(\Barryvdh\DomPDF\PDF::class)) {
            return;
        }

        $configPath = base_path('vendor/barryvdh/laravel-dompdf/config/dompdf.php');
        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'dompdf');
        }

        $this->app->bindIf('dompdf.options', function ($app) {
            $defines = $app['config']->get('dompdf.defines');

            if ($defines) {
                $options = [];
                foreach ($defines as $key => $value) {
                    $key = strtolower(str_replace('DOMPDF_', '', $key));
                    $options[$key] = $value;
                }

                return $options;
            }

            return $app['config']->get('dompdf.options');
        });

        $this->app->bindIf('dompdf', function ($app) {
            $options = $app->make('dompdf.options');
            $dompdf = new \Dompdf\Dompdf($options);
            $path = realpath($app['config']->get('dompdf.public_path') ?: base_path('public'));
            if ($path === false) {
                $path = base_path('public');
            }
            $dompdf->setBasePath($path);

            return $dompdf;
        });

        if (! $this->app->bound(\Dompdf\Dompdf::class)) {
            $this->app->alias('dompdf', \Dompdf\Dompdf::class);
        }

        $this->app->bindIf('dompdf.wrapper', function ($app) {
            return new \Barryvdh\DomPDF\PDF($app['dompdf'], $app['config'], $app['files'], $app['view']);
        });
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


