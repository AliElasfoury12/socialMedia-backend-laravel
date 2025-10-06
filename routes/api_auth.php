<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgetPasswordController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware'=>['jwt_auth','throttle:api']], function () {
    Route::controller(AuthController::class)->group(function () 
    {
        //Route::get('logout','logout');
        Route::get('user','user');
        Route::post('changePassword','changePassword');
    });
});

Route::group(['middleware'=>['throttle:api']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login','login');
    });

    Route::controller(ForgetPasswordController::class)->group(function () {
        Route::post('find_user_and_send_otp','findUserAndSendOTP');
        Route::post('resend-otp', 'resendOTP');
        Route::post('check-otp', 'checkOtp');
        Route::patch('set_new_password','setNewPassword');
    });
});