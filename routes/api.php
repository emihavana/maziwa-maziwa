<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Farmer;
use App\Models\Route as MilkRoute;

/*
|--------------------------------------------------------------------------
| Public Endpoints (Anyone can access these)
|--------------------------------------------------------------------------
*/

// Test Endpoint 1: Get all routes and their basic information
Route::get('/public/routes', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Public routes fetched successfully',
        'system_time' => now()->toDateTimeString(),
        'total_count' => MilkRoute::count(),
        'data' => MilkRoute::select('id', 'name')->get()
    ], 200);
});


/*
|--------------------------------------------------------------------------
| Protected Endpoints (Requires a Sanctum Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Test Endpoint 2: Get full farmer details securely
    Route::get('/secure/farmers', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Secure farmer directory accessed',
            'data' => Farmer::select('farmer_id', 'name', 'phone', 'route_id')->get()
        ], 200);
    });
});