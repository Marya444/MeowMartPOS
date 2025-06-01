<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // 🔓 Public routes
    // Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/login', [AuthController::class, 'login']);

    // // 🔐 Protected routes
    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::post('/logout', [AuthController::class, 'logout']);
    // });

    // Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function () {
    //     Route::apiResource('/products', ProductController::class);
    // });

    // Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    //     Route::get('/admin-only', fn() => response()->json(['message' => 'Welcome Admin']));
    // });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::get('/products-low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products-search', [ProductController::class, 'search']);
});

});
