<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ClaimController as AdminClaimController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PasswordResetController as AdminPasswordResetController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleModelController as AdminVehicleModelController;
use App\Http\Controllers\Admin\WarrantyCodeController as AdminWarrantyCodeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VehicleCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
});

Route::view('/terms-of-service', 'legal.terms')->name('terms');
Route::view('/privacy-policy', 'legal.privacy')->name('privacy');
Route::get('/vehicle-data-credits', fn () => view('legal.vehicle-data-credits', [
    'credits' => file_get_contents(database_path('data/VEHICLE_DATA_ATTRIBUTION.md')),
]))->name('vehicle-data.credits');
Route::get('/vehicle-models/{model}/photo', function (\App\Models\VehicleModel $model) {
    abort_unless($model->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($model->photo_path), 404);
    return \Illuminate\Support\Facades\Storage::disk('public')->response($model->photo_path);
})->name('catalog.models.photo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/free/{plan}', [SubscriptionController::class, 'activateFree'])->middleware('throttle:10,1')->name('subscription.free');
    Route::post('/subscription/skip', [SubscriptionController::class, 'skip'])->name('subscription.skip');
    Route::post('/payments/order', [PaymentController::class, 'order'])->middleware('throttle:10,1')->name('payments.order');
    Route::post('/payments/verify', [PaymentController::class, 'verify'])->middleware('throttle:15,1')->name('payments.verify');
    Route::get('/subscription/success', [PaymentController::class, 'success'])->name('subscription.success');

    Route::middleware(['onboarded', 'subscription.active'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'overview'])->name('dashboard');
        Route::post('/warranty-code/email', [DashboardController::class, 'emailWarrantyCode'])->middleware('throttle:3,1')->name('warranty-code.email');
        Route::get('/my-vehicles', [DashboardController::class, 'vehicles'])->name('vehicles.index');
        Route::get('/my-warranty', [DashboardController::class, 'warranty'])->name('warranty.show');
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/create', [ClaimController::class, 'create'])->name('claims.create');
        Route::get('/catalog/makes/{make}/models', [VehicleCatalogController::class, 'models'])->name('catalog.make.models');
        Route::get('/catalog/models/{model}', [VehicleCatalogController::class, 'show'])->name('catalog.model.show');
        Route::post('/claims/warranty-code/check', [ClaimController::class, 'checkWarrantyCode'])->middleware('throttle:30,1')->name('claims.warranty-code.check');
        Route::post('/claims', [ClaimController::class, 'store'])->middleware('throttle:10,1')->name('claims.store');
        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
        Route::get('/claims/{claim}/photos/{index}', [ClaimController::class, 'photo'])->whereNumber('index')->name('claims.photo');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{subscription}/certificate', [DocumentController::class, 'certificate'])->name('documents.certificate');
        Route::get('/documents/payments/{payment}/invoice', [DocumentController::class, 'invoice'])->name('documents.invoice');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/forgot-password', [AdminPasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [AdminPasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::resource('plans', AdminPlanController::class)->except('show');
        Route::resource('customers', AdminCustomerController::class)->except('show');
        Route::resource('vehicles', AdminVehicleController::class)->parameters(['vehicles' => 'vehicle']);
        Route::patch('/vehicles/{vehicle}/toggle', [AdminVehicleController::class, 'toggle'])->name('vehicles.toggle');
        Route::resource('vehicles.models', AdminVehicleModelController::class)->except('index')->parameters(['vehicles' => 'vehicle', 'models' => 'model']);
        Route::patch('/vehicles/{vehicle}/models/{model}/toggle', [AdminVehicleModelController::class, 'toggle'])->name('vehicles.models.toggle');
        Route::get('/claims/report', [AdminClaimController::class, 'report'])->name('claims.report');
        Route::get('/claims/{claim}/photos/{index}', [AdminClaimController::class, 'photo'])->whereNumber('index')->name('claims.photo');
        Route::get('/claims/{claim}/model-photo', [AdminClaimController::class, 'modelPhoto'])->name('claims.model-photo');
        Route::resource('claims', AdminClaimController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('/payments/report', [AdminPaymentController::class, 'report'])->name('payments.report');
        Route::resource('payments', AdminPaymentController::class)->only(['index', 'show']);
        Route::get('/warranty-codes', [AdminWarrantyCodeController::class, 'index'])->name('warranty-codes.index');
        Route::post('/warranty-codes', [AdminWarrantyCodeController::class, 'store'])->name('warranty-codes.store');
        Route::get('/warranty-codes/{warrantyCode}', [AdminWarrantyCodeController::class, 'show'])->name('warranty-codes.show');
        Route::patch('/warranty-codes/{warrantyCode}/toggle', [AdminWarrantyCodeController::class, 'toggle'])->name('warranty-codes.toggle');
        Route::delete('/warranty-codes/{warrantyCode}', [AdminWarrantyCodeController::class, 'destroy'])->name('warranty-codes.destroy');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    });
});
