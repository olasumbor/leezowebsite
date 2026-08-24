<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProcurementController;
use App\Http\Controllers\Api\ShipmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Remote Migration Helper
Route::get('/migrate', function (Request $request) {
    try {
        if ($request->has('fresh') || $request->has('clean')) {
            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        }
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return response()->json([
            'message' => 'Clean migrations and seeders executed successfully!',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Migration failed',
            'details' => $e->getMessage()
        ], 500);
    }
});

use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\PickupDeliveryController;
use App\Http\Controllers\Api\FrozenCargoController;

// Password Reset Routes
Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// Public tracking route
Route::get('/track/{tracking_id}', [ShipmentController::class, 'track']);

// Public routes for Quotes, Contact, Newsletter, Pickup & Delivery, Frozen Cargo
Route::post('/quotes/calculate', [QuoteController::class, 'store']);
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/pickup-deliveries', [PickupDeliveryController::class, 'store']);
Route::post('/frozen-cargos', [FrozenCargoController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    
    // User Procurements
    Route::post('/procurements', [ProcurementController::class, 'store']);
    Route::get('/procurements', [ProcurementController::class, 'index']);
    Route::get('/procurements/{id}', [ProcurementController::class, 'show']);
    Route::get('/procurements/{id}/receipt', [ProcurementController::class, 'downloadReceipt']);
    Route::get('/procurements/{id}/invoice', [ProcurementController::class, 'downloadInvoice']);
    
    // User Shipments
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/stats', [ShipmentController::class, 'stats']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
    Route::get('/shipments/{id}/receipt', [ShipmentController::class, 'downloadReceipt']);
    Route::get('/shipments/{id}/invoice', [ShipmentController::class, 'downloadInvoice']);

    // User Pickup & Deliveries
    Route::get('/pickup-deliveries', [PickupDeliveryController::class, 'index']);
    Route::get('/pickup-deliveries/{id}', [PickupDeliveryController::class, 'show']);

    // User Frozen Cargos
    Route::get('/frozen-cargos', [FrozenCargoController::class, 'index']);
    Route::get('/frozen-cargos/{id}', [FrozenCargoController::class, 'show']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Admin Overview
    Route::get('/overview', [AdminController::class, 'overview']);
    
    // Admin Users
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}/password', [UserController::class, 'updatePassword']);

    // Admin Procurements
    Route::get('/procurements', [ProcurementController::class, 'adminIndex']);
    Route::get('/procurements/{id}', [ProcurementController::class, 'adminShow']);
    Route::put('/procurements/{id}', [ProcurementController::class, 'adminUpdate']);
    Route::put('/procurements/{id}/status', [ProcurementController::class, 'updateStatus']);
    
    // Admin Shipments
    Route::get('/shipments', [ShipmentController::class, 'adminIndex']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'adminShow']);
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::put('/shipments/{id}', [ShipmentController::class, 'adminUpdate']);
    Route::put('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);
    Route::post('/shipments/{id}/events', [ShipmentController::class, 'addEvent']);

    // Admin Pickup & Deliveries
    Route::get('/pickup-deliveries', [PickupDeliveryController::class, 'adminIndex']);
    Route::put('/pickup-deliveries/{id}/status', [PickupDeliveryController::class, 'updateStatus']);

    // Admin Frozen Cargos
    Route::get('/frozen-cargos', [FrozenCargoController::class, 'adminIndex']);
    Route::put('/frozen-cargos/{id}/status', [FrozenCargoController::class, 'updateStatus']);

    // Admin Quotes
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::put('/quotes/{id}/cost', [QuoteController::class, 'setCost']);

    // Admin Contact & Newsletter
    Route::get('/contact-messages', [ContactController::class, 'index']);
    Route::get('/newsletter-subscribers', [NewsletterController::class, 'index']);

    // Admin Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
});

