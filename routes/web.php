<?php

namespace App\Http\Controllers\Front;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

    Route::controller(PageController::class)->group(function(){
    Route::get('privacy-policy', 'privacyPolicy')->name('front.privacy-policy');
        Route::get('terms-and-conditions', 'termsAndConditions')->name('front.terms-and-conditions');
    });
    Route::get('/generate-kit-token', [TwilioController::class, 'generateKitToken'])->name('front.generate-kit-token');

    Route::get('/', function () {
        return view('welcome');
    });
    
    
    //clear cache route
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        echo '<script>alert("cache clear Success")</script>';
    });
    Route::get('/config-cache', function () {
        Artisan::call('config:cache');
        echo '<script>alert("config cache Success")</script>';
    });
    Route::get('/view', function () {
        Artisan::call('view:clear');
        echo '<script>alert("view clear Success")</script>';
    });
    Route::get('/route', function () {
        Artisan::call('route:cache');
        echo '<script>alert("route clear Success")</script>';
    });
    Route::get('/config-clear', function () {
        Artisan::call('config:clear');
        echo '<script>alert("config clear Success")</script>';
    });
    Route::get('/storage', function () {
        Artisan::call('storage:link');
        echo '<script>alert("linked")</script>';
    });
   
            

    
