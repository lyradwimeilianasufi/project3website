<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\StokController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BarangKeluarController;

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
Route::get('/test', function () {
    return response()->json([
        'message' => 'API route working!'
    ]);
});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Product routes (public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Contact routes (public)
Route::post('/contact', [ContactController::class, 'store']);

// Barang Masuk routes (public read, protected write)
Route::get('/barang-masuk', [BarangMasukController::class, 'index']);
Route::get('/barang-masuk/{id}', [BarangMasukController::class, 'show']);

// Barang Keluar routes (public read, protected write)
Route::get('/barang-keluar', [BarangKeluarController::class, 'index']);
Route::get('/barang-keluar/{id}', [BarangKeluarController::class, 'show']);
Route::get('/barang-keluar/transaction/{transactionId}', [BarangKeluarController::class, 'getByTransaction']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);

    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::get('/cart/count', [CartController::class, 'getCartCount']);

    // Invoice routes
    Route::get('/invoices', [InvoiceController::class, 'userInvoices']);
    Route::get('/invoices/{order_id}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{order_id}/download', [InvoiceController::class, 'download']);

    // Protected write routes
    Route::post('/barang-masuk', [BarangMasukController::class, 'store']);
    Route::put('/barang-masuk/{id}', [BarangMasukController::class, 'update']);
    Route::delete('/barang-masuk/{id}', [BarangMasukController::class, 'destroy']);

    Route::post('/barang-keluar', [BarangKeluarController::class, 'store']);

    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('/contact', [ContactController::class, 'index']);
        Route::get('/contact/{id}', [ContactController::class, 'show']);
        Route::delete('/contact/{id}', [ContactController::class, 'destroy']);
        Route::post('/contact/{id}/read', [ContactController::class, 'markAsRead']);
        
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });
});
// Member routes
Route::prefix('members')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::post('/', [MemberController::class, 'store']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'destroy']);
});

// Payment routes
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::get('/checkout', [PaymentController::class, 'checkout']);
    Route::post('/process', [PaymentController::class, 'processPaymentMobile']);
    Route::post('/{transactionId}/confirm', [PaymentController::class, 'confirmPayment']);
    Route::get('/{transactionId}/status', [PaymentController::class, 'getPaymentStatus']);
    
    Route::get('/transactions', [PaymentController::class, 'getUserTransactions']);
    Route::get('/snap-token/{snapToken}', [PaymentController::class, 'getTransactionBySnapToken']);
    Route::post('/{transactionId}/regenerate-snap-token', [PaymentController::class, 'regenerateSnapToken']);
});

Route::prefix('payments')->group(function () {
    Route::get('/{transactionId}/check-status', [PaymentController::class, 'checkTransactionStatus']);
    Route::post('/payments/test-callback', [PaymentController::class, 'testCallback']);
    Route::post('/callback/mobile', [PaymentController::class, 'mobileCallback']);
    Route::post('/callback/finish', [PaymentController::class, 'mobileCallback']);
    Route::post('/callback/error', [PaymentController::class, 'mobileCallback']);
    Route::post('/callback/pending', [PaymentController::class, 'mobileCallback']);
});
// Product routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/categories', [ProductController::class, 'categories']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/low-stock/alerts', [ProductController::class, 'lowStockAlerts']);

// Protected product routes (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
});

// Stock routes
Route::prefix('stock')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/', [StokController::class, 'index']);
    Route::put('/{id}', [StokController::class, 'updateStock']);
    Route::get('/alerts', [StokController::class, 'lowStockAlerts']);
});

// Supplier routes
Route::prefix('suppliers')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/', [SupplierController::class, 'index']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::put('/{id}', [SupplierController::class, 'update']);
    Route::delete('/{id}', [SupplierController::class, 'destroy']);
});

// Transaction routes
Route::prefix('transactions')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/', [TransaksiController::class, 'index']);
    Route::get('/statistics', [TransaksiController::class, 'statistics']);
    Route::get('/{transactionId}', [TransaksiController::class, 'show']);
});

// User transaction routes
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard']);
    Route::get('/orders', [UserController::class, 'orders']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::get('/transactions', [TransaksiController::class, 'userTransactions']);
});
// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Endpoint not found'
    ], 404);
});