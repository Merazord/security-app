<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\IsVerified;
use App\Http\Middleware\IsActive;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Home Route
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Registration Routes
Route::get('/register', [RegisterController::class, 'registerform'])->name('register.form');

Route::post('/register', [RegisterController::class, 'register'])->name('register');

// Activation Routes
Route::get('/activate/{user}', [RegisterController::class, 'activate'])
    ->name('activate');

// Resend Activation Email Route
Route::post('/resend-activation-email', [RegisterController::class, 'resendActivationEmail'])
    ->name('resend.activation.email');



Route::middleware('web')->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [LoginController::class, 'login'])->name('login');

    Route::middleware('auth')->group(function () {
        Route::get('/verification', [LoginController::class, 'showVerificationForm'])->name('verification');
        Route::post('/verification', [LoginController::class, 'verification'])->name('verification.submit');
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');


        // Logout Route
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    });
});
