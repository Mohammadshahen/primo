<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [UserManagementController::class, 'register']);
Route::post('confirm-registration', [UserManagementController::class, 'confirmRegistration']);

// تسجيل الدخول والتأكيد
Route::post('login', [UserManagementController::class, 'login'])->name('login');
Route::post('confirm-login', [UserManagementController::class, 'confirmLogin']);
Route::post('refresh', [UserManagementController::class, 'refreshToken']);


// نسيان كلمة المرور (منفصل)
Route::post('forgot-password', [UserManagementController::class, 'forgotPassword']);
Route::post('confirm-forgot-password', [UserManagementController::class, 'confirmForgotPassword']);
Route::post('reset-password', [UserManagementController::class, 'resetPassword']);

// إعادة إرسال OTP
Route::post('resend-otp', [UserManagementController::class, 'resendOTP']);

Route::middleware('auth:sanctum')->post('/logout', [UserManagementController::class, 'logout']);
Route::middleware('auth:sanctum')->delete('/account/delete', [UserManagementController::class, 'deleteAccount']);


Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategorieController::class);
    Route::apiResource('offers', OfferController::class);

    Route::apiResource('products', ProductController::class);
    Route::delete('variants/{variant}/delete', [ProductController::class, 'deleteVariant'])->name('products.variants.destroy');
    Route::post('products/toggle-active/{product}', [ProductController::class, 'toggleStatus'])->name('products.toggle-active');
});


Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('categories', [CategorieController::class, 'userGitAllGategories'])->name('user.categories.index');
    Route::get('home', [HomeController::class, 'userHome'])->name('user.home');
    Route::get('products/{product}', [ProductController::class, 'getProductById'])->name('user.products.show');

    Route::get('profile', [UserController::class, 'getProfileUser'])->name('user.profile.show');
    Route::patch('profile', [UserController::class, 'updateProfileUser'])->name('user.profile.update');
    Route::post('change-password', [UserController::class, 'changePasswordUser'])->name('user.profile.change-password');
    Route::get('notifications', [UserController::class, 'getNotificationSettingsUser'])->name('user.notifications.show');
    Route::patch('notifications', [UserController::class, 'updateNotificationSettingsUser'])->name('user.notifications.update');
    Route::post('favorites/toggle/{product}', [UserController::class, 'toggleFavoriteUser'])->name('user.favorites.toggle');
    Route::get('favorites', [UserController::class, 'getFavoriteProductsUser'])->name('user.favorites.index');

    Route::get('cart', [CartController::class, 'index'])->name('user.cart.index');
    Route::post('cart', [CartController::class, 'store'])->name('user.cart.store');
    Route::patch('cart/{cart}', [CartController::class, 'update'])->name('user.cart.update');
    Route::delete('cart/{cart}', [CartController::class, 'destroy'])->name('user.cart.destroy');

    Route::apiResource('addresses', AddressController::class);
});