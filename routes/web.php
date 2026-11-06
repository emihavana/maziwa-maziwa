<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MilkController;

use App\Http\Controllers\FarmerReportController;
use App\Http\Middleware\KeepCollectorsOutOfAdmin;

//use App\Models\Farmer;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return redirect()->to($user->role === 'admin' ? '/admin' : '/collector');
    }

    return redirect()->route('filament.admin.auth.login');
});

use App\Http\Middleware\EnsureUserIsCollector;

Route::view('/collector', 'collector.dashboard')
    ->middleware(['auth', EnsureUserIsCollector::class])
    ->name('collector.dashboard');

//route to redirect  to login page if user is not logged in in the collector dashboard
Route::redirect('/login', '/admin/login')->name('login');
//route to get the farmer report download page
Route::get('/reports/farmers', [FarmerReportController::class, 'download'])
    ->middleware(['auth', KeepCollectorsOutOfAdmin::class])
    ->name('reports.farmers');
