<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FarmerPayoutController;

/*
|--------------------------------------------------------------------------
| M-Pesa callbacks (called by Safaricom, not by browsers)
|--------------------------------------------------------------------------
| Laravel adds the /api prefix automatically, so the public URLs are:
|   POST https://your-domain/api/mpesa/b2c/result
|   POST https://your-domain/api/mpesa/b2c/timeout
|
| These routes must stay public (no auth middleware), because Safaricom
| cannot log in. API routes are also exempt from CSRF checks.
*/
Route::prefix('mpesa/b2c')->group(function () {
    Route::post('result',  [FarmerPayoutController::class, 'handleResult'])->name('mpesa.b2c.result');
    Route::post('timeout', [FarmerPayoutController::class, 'handleTimeout'])->name('mpesa.b2c.timeout');
});