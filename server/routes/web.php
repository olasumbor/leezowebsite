<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Cache Clear Helper Route
Route::get('/clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        return response()->json([
            'message' => 'View, config, route, and application caches cleared successfully!',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Cache clearing failed',
            'details' => $e->getMessage()
        ], 500);
    }
});
