<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrdarController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SuggestionController;
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


    Route::get('ordars', [OrdarController::class, 'getAllOrdar'])->name('admin.ordars.index');
    Route::get('ordars/{ordar}', [OrdarController::class, 'getSingleOrdar'])->name('admin.ordars.show');
    Route::post('ordars/status/{ordar}', [OrdarController::class, 'changeOrdarStatus'])->name('admin.ordars.status.change');


    Route::get('settings/delivery-price', [SettingController::class, 'getDeliveryPrice'])->name('admin.settings.delivery-price.show');
    Route::patch('settings/delivery-price', [SettingController::class, 'updateDeliveryPrice'])->name('admin.settings.delivery-price.update');
    Route::delete('variants/{variant}/delete', [ProductController::class, 'deleteVariant'])->name('products.variants.destroy');
    Route::post('products/toggle-active/{product}', [ProductController::class, 'toggleStatus'])->name('products.toggle-active');
    Route::post('address', [AddressController::class, 'saveAdminAddress'])->name('admin.store-address.save');


    // Admin routes for suggestions
    Route::post('suggestions/{suggestion}/status', [SuggestionController::class, 'changeStatus'])->name('admin.suggestions.status.change');


    Route::get('home', [HomeController::class, 'adminHome'])->name('admin.home');
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

    Route::post('products/{product}/rate/ordar/{ordar}', [UserController::class, 'rateProduct'])->name('user.products.rate');

    Route::get('cart', [CartController::class, 'index'])->name('user.cart.index');
    Route::post('cart', [CartController::class, 'store'])->name('user.cart.store');
    Route::patch('cart/{cart}', [CartController::class, 'update'])->name('user.cart.update');
    Route::delete('cart/{cart}', [CartController::class, 'destroy'])->name('user.cart.destroy');

    Route::post('ordar/price', [OrdarController::class, 'OrdarPrice'])->name('user.ordars.price');
    Route::post('ordar/confirme', [OrdarController::class, 'confirmeOrdar'])->name('user.ordars.confirme');



    Route::apiResource('addresses', AddressController::class);
    // User route to submit a suggestion
    Route::post('suggestions', [SuggestionController::class, 'store'])->name('user.suggestions.store');
});
