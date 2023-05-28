<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::fallback(function () {
    return response()->json([
        'ResponseCode'  => 404,
        'status'        => False,
        'message'       => 'URL not found as you looking'
    ]);
});//->name('api.unauthorized');
// sendVoip

Route::get('unauthorized', function () {
    return response()->json(['statusCode' => 401, 'status' => 'unauthorized', 'message' => 'Unauthorized user.']);
})->name('api.unauthorized');

/*
        |--------------------------------------------------------------------------
        | AUTH REGISTER LOGIN SENT LOGIN OTP ROUTE
        |--------------------------------------------------------------------------
        */
        Route::controller(AuthController::class)->group(function () {
            Route::post('login', 'login');
            Route::get('deletefromtemp', 'deletefromtemp');
            Route::post('sendVoip', 'sendVoip');
            Route::post('login_otp_verify', 'loginOtpVerify');
            // Route::post('forgot_password', 'forgotPassword');
            // Route::post('update_forget_password', 'updateForgetPassword');
            Route::post('login_with_social_media', 'loginWithSocialMedia');
        });
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(AuthController::class)->group(function(){
            Route::post('update_profile','updateUserProfile');    
            Route::get('profile','getUserProfile');    
        Route::get('get_language','getLanguage');    
        Route::get('get_topic','getTopic');    
        Route::post('update_topic','updateTopic');    
        Route::get('get_my_topic','getMyTopic');    
        Route::get('delete_my_account', 'deleteMyAccount');
        Route::get('get_notifications', 'getNotification');
        Route::get('clearNotification', 'clearNotification');
        Route::get('get_ratting', 'getRatting');
        Route::post('change_visible_status', 'changeVisibleStatus');
        Route::post('save_ratting', 'saveRatting');
        Route::get('find_my_match_connection','findMyMatchConnection');
        Route::post('sent_device_token','sentDeviceToken');
        Route::get('call_history','callHistory');
        Route::get('version', 'version');
        
        });

        Route::controller(TwilioController::class)->group(function(){
            Route::get('/generate_token','generateTokens');
            Route::post('/send_notification','sendNotification');
        });
    });
