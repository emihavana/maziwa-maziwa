<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MilkController;
//use App\Models\Farmer;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return redirect()->to($user->role === 'admin' ? '/admin' : '/collector');
    }

    return redirect()->route('filament.admin.auth.login');
});

use App\Http\Middleware\EnsureUserIsCollector;

Route::middleware(['auth', EnsureUserIsCollector::class])->group(function () {
    Route::view('/collector', 'collector.dashboard')->name('collector.dashboard');
});
