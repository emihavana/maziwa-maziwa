<?php
use App\Http\Controllers\FarmerPayoutController;

// 1. Endpoint triggered from your admin dashboard or button to start end-month disbursements
Route::post('/farmers/payouts/run', [FarmerPayoutController::class, 'disburseEndMonthBalances']);

// 2. Webhook endpoint where Safaricom posts the transaction result callback asynchronously
Route::post('/mpesa/b2c/result', [FarmerPayoutController::class, 'handleCallback']);