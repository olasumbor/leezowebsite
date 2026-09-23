<?php

use App\Providers\AppServiceProvider;
use Barryvdh\DomPDF\ServiceProvider;

return [
    AppServiceProvider::class,
    // Kept explicit (in addition to package auto-discovery) so shared-hosting
    // deploys without `composer install` scripts / `package:discover` still
    // register dompdf. Duplicate registration with auto-discovery is harmless
    // (bindings are simply re-registered).
    ServiceProvider::class,
];
