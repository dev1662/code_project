<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SignUpController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\ForgotOrganizationController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('signup')->group(function(){
    Route::post('send-email', [SignUpController::class, 'sendEmail']);
    Route::post('verify-email', [SignUpController::class, 'verifyEmail']);
    Route::put('organization', [SignUpController::class, 'organization']);
    Route::post('complete', [SignUpController::class, 'complete']);
});

// Route::post('choose-organization', [ChooseOrganizationController::class, 'index']);
Route::post('login', [LoginController::class, 'index']);
Route::post('forgot-password', [ForgotPasswordController::class, 'index']);
Route::post('reset-password', [ResetPasswordController::class, 'update']);
Route::post('forgot-organization', [ForgotOrganizationController::class, 'index']);
Route::post('invitation/check', [InvitationController::class, 'check']);
Route::post('invitation/accept', [InvitationController::class, 'accept']);
Route::post('invitation/decline', [InvitationController::class, 'decline']);