<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // Admin: Get all settings
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    // Admin: Update settings
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    // Admin: Clear system cache (artisan optimize:clear)
    public function clearCache()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'message' => 'System, view, route, and config caches cleared successfully!',
                'output' => trim($output)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    // Admin: Run database migrations (artisan migrate)
    public function runMigrations(Request $request)
    {
        try {
            $isFresh = filter_var($request->input('fresh'), FILTER_VALIDATE_BOOLEAN);
            $shouldSeed = filter_var($request->input('seed'), FILTER_VALIDATE_BOOLEAN);

            $output = '';

            if ($isFresh) {
                \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
                $output .= \Illuminate\Support\Facades\Artisan::output();
            } else {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $output .= \Illuminate\Support\Facades\Artisan::output();
            }

            if ($shouldSeed) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                $output .= "\n" . \Illuminate\Support\Facades\Artisan::output();
            }

            return response()->json([
                'message' => 'Database migrations executed successfully!',
                'output' => trim($output)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
