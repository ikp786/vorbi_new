<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application admin panel.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "admin" middleware group. Now create something great!
|
*/
// Route::get('/login', [AdminAuthController::class, 'index'])->name('login');


// Route::get('login',[DashboardController::class,'index'])->name('login');
Route::get('/testnotification',  [AdminAuthController::class, 'testnotification']);
Route::name('admin.')->group(function () {
    Route::middleware('guest')->group(
        function () {
            Route::get('/', [AdminAuthController::class, 'index']);
            Route::get('/login', [AdminAuthController::class, 'index'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login']);
        }
    );
    /*
    |--------------------------------------------------------------------------
    |AUTHENTIC ROUTE
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(
        function () {
            // Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
            Route::resources([
                'dashboard' => DashboardController::class,
                'topics' => TopicController::class,
                'languages' => LanguageController::class,
                'users' => UserController::class,
                'pages' => PageController::class
            ]);
            Route::controller(AdminAuthController::class)->group(function () {
                Route::post('logout', 'logout')->name('logout');
                Route::get('password-change', 'changePasswordGet')->name('password-change');
                Route::post('change-password',  'changePassword')->name('change-password');
            });
            Route::get('topics-status', [TopicController::class, 'changeStatus'])->name('topics.status');
            Route::get('languages-status', [LanguageController::class, 'changeStatus'])->name('languages.status');
            Route::get('users-status', [UserController::class, 'changeStatus'])->name('users.status');
        }
    );
});