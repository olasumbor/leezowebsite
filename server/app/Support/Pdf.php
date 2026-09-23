<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\App;

/**
 * Central helper for generating PDFs.
 *
 * Uses the `dompdf.wrapper` binding when available (normal path via
 * Barryvdh\DomPDF\ServiceProvider auto-discovery / explicit registration),
 * and falls back to constructing the wrapper manually when the production
 * host is running with a stale bootstrap/cache/services.php + packages.php
 * (which caused "Target class [dompdf.wrapper] does not exist.").
 */
class Pdf
{
    public static function loadView(string $view, array $data = [], array $mergeData = [], ?string $encoding = null): DomPdfWrapper
    {
        return static::wrapper()->loadView($view, $data, $mergeData, $encoding);
    }

    public static function loadHTML(string $html, ?string $encoding = null): DomPdfWrapper
    {
        return static::wrapper()->loadHTML($html, $encoding);
    }

    public static function wrapper(): DomPdfWrapper
    {
        static::ensureFontDirectories();

        if (App::bound('dompdf.wrapper')) {
            return App::make('dompdf.wrapper');
        }

        if (App::bound('dompdf')) {
            $dompdf = App::make('dompdf');
        } else {
            $options = config('dompdf.options', []);
            $dompdf = new Dompdf(is_array($options) ? $options : []);
            $basePath = realpath(config('dompdf.public_path') ?: base_path('public')) ?: base_path('public');
            $dompdf->setBasePath($basePath);
        }

        return new DomPdfWrapper($dompdf, config(), app('files'), app('view'));
    }

    /**
     * Dompdf fails at render time if font_dir / font_cache / temp_dir are
     * missing or unwritable, so make a best-effort attempt to create them.
     */
    protected static function ensureFontDirectories(): void
    {
        foreach ([
            config('dompdf.options.font_dir', storage_path('fonts')),
            config('dompdf.options.font_cache', storage_path('fonts')),
            config('dompdf.options.temp_dir', sys_get_temp_dir()),
        ] as $dir) {
            if (is_string($dir) && $dir !== '' && ! is_dir($dir)) {
                try {
                    @mkdir($dir, 0755, true);
                } catch (\Throwable $e) {
                    // Best effort only; dompdf will surface a clearer error.
                }
            }
        }
    }
}
