<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Users\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//auth
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('api.verify-email');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/profile', [UsersController::class, 'profile'])->name('api.profile');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->name('api.refresh-token');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});
