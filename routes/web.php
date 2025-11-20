<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacilityRegistrationController;

// React App Route (serve React frontend)
Route::get('/app{any?}', function () {
    return view('react-app');
})->where('any', '.*');

// Facility Registration Routes
Route::get('/register-facility', [FacilityRegistrationController::class, 'show'])->name('facility-registration.show');
Route::post('/register-facility', [FacilityRegistrationController::class, 'store'])->name('facility-registration.store');
Route::get('/register-facility/success', [FacilityRegistrationController::class, 'success'])->name('facility-registration.success');

// Redirect root to React app login
Route::get('/', function () {
    return redirect('/app/login');
});
