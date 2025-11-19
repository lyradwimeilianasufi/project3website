<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\BarangKeluarController;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () { return view('welcome'); })->name('welcome');
Route::get('/mitra', function () { return view('mitra'); })->name('mitra');
Route::get('/profil', function () { return view('visi'); })->name('profil');

// Produk
Route::get('/produk', [ProductController::class, 'showProducts'])->name('produk');

// Kontak
Route::get('/kontak', [ContactController::class, 'index'])->name('kontak');
Route::post('/kontak/store', [ContactController::class, 'store'])->name('kontak.store');
// Route::get('/kontak', function () { return view('kontak'); })->name('kontak');

// Route login admin
Route::get('/admin', [AuthController::class, 'showAdminLoginForm'])->name('admin.login');
Route::post('/admin', [AuthController::class, 'adminLogin'])->name('admin.login.submit');

// Dashboard admin
Route::get('/admin/index', [AdminController::class, 'Dashboard'])->name('admin.dashboard');

// Pesan masuk
Route::get('/admin/contact', [ContactController::class, 'showMessages'])
    ->middleware('auth.admin')
    ->name('admin.messages');

/*
|--------------------------------------------------------------------------
| Member
|--------------------------------------------------------------------------
*/

Route::get('/admin/member', [DashboardController::class, 'member'])->middleware('auth.admin')->name('admin.member');
Route::get('/admin/member/new', [DashboardController::class, 'newMember'])->middleware('auth.admin')->name('admin.new-member');
Route::get('/admin/member/create', [MemberController::class, 'create'])->middleware('auth.admin')->name('admin.member.create');
Route::post('/admin/member', [MemberController::class, 'store'])->middleware('auth.admin')->name('admin.member.store');
Route::get('/admin/member/edit/{id}', [DashboardController::class, 'editMember'])->middleware('auth.admin')->name('admin.member.edit');
Route::post('/admin/member/update/{id}', [DashboardController::class, 'updateMember'])->middleware('auth.admin')->name('admin.member.update');
Route::delete('/admin/member/delete/{id}', [DashboardController::class, 'deleteMember'])->middleware('auth.admin')->name('admin.member.delete');

/*
|--------------------------------------------------------------------------
| Supplier
|--------------------------------------------------------------------------
*/

Route::get('/admin/supplier', [SupplierController::class, 'index'])->middleware('auth.admin')->name('admin.supplier');
Route::get('/admin/supplier/new', [SupplierController::class, 'create'])->middleware('auth.admin')->name('admin.new-supplier');
Route::get('/admin/supplier/create', [SupplierController::class, 'create'])->middleware('auth.admin')->name('admin.supplier.create');
Route::get('/admin/supplier/{id}/edit', [SupplierController::class, 'edit'])->middleware('auth.admin')->name('admin.supplier.edit');
Route::put('/admin/supplier/{id}', [SupplierController::class, 'update'])->middleware('auth.admin')->name('admin.supplier.update');
Route::delete('/admin/supplier/{id}', [SupplierController::class, 'destroy'])->middleware('auth.admin')->name('admin.supplier.destroy');
Route::post('/admin/supplier', [SupplierController::class, 'store'])->middleware('auth.admin')->name('admin.supplier.store');

/*
|--------------------------------------------------------------------------
| Produk
|--------------------------------------------------------------------------
*/

Route::get('/admin/produk', [ProductController::class, 'produk'])->middleware('auth.admin')->name('admin.produk');
Route::get('/admin/produk/new', [ProductController::class, 'newProduk'])->middleware('auth.admin')->name('admin.new-produk');
Route::post('/admin/produk', [ProductController::class, 'store'])->middleware('auth.admin')->name('admin.produk.store');
Route::get('/admin/produk/{id}/edit', [ProductController::class, 'edit'])->middleware('auth.admin')->name('admin.produk.edit');
Route::delete('/admin/produk/{id}', [ProductController::class, 'destroy'])->middleware('auth.admin')->name('admin.produk.destroy');
Route::put('/admin/produk/{id}', [ProductController::class, 'update'])->middleware('auth.admin')->name('admin.produk.update');

/*
|--------------------------------------------------------------------------
| Stok
|--------------------------------------------------------------------------
*/

Route::get('/admin/stok', [StokController::class, 'index'])->middleware('auth.admin')->name('admin.stok');

/*
|--------------------------------------------------------------------------
| Barang Masuk & Keluar
|--------------------------------------------------------------------------
*/

Route::get('/admin/barang-masuk', [BarangMasukController::class, 'index'])->middleware('auth.admin')->name('admin.barang-masuk');
Route::post('/admin/barang-masuk', [BarangMasukController::class, 'store'])->middleware('auth.admin')->name('admin.barang-masuk.store');

Route::get('/admin/barang-keluar', [BarangKeluarController::class, 'index'])->middleware('auth.admin')->name('admin.barang-keluar');
Route::post('/admin/barang-keluar', [BarangKeluarController::class, 'store'])->middleware('auth.admin')->name('admin.barang-keluar.store');

/*
|--------------------------------------------------------------------------
| Transaksi
|--------------------------------------------------------------------------
*/

Route::get('/admin/transaksi', [TransaksiController::class, 'index'])->middleware('auth.admin')->name('admin.transaksi');
Route::get('/admin/transaksi/{transactionId}', [TransaksiController::class, 'show'])->middleware('auth.admin')->name('admin.transaksi.show');

// Logout admin
Route::post('/admin/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| User Auth
|--------------------------------------------------------------------------
*/

// Route login user
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Dashboard user
Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard')->middleware('auth:web');
Route::get('/assistant', [ChatbotController::class, 'index'])->name('assistant')->middleware('auth:web');

/*
|--------------------------------------------------------------------------
| Keranjang
|--------------------------------------------------------------------------
*/

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

/*
|--------------------------------------------------------------------------
| Checkout + Midtrans (PEMBAYARAN)
|--------------------------------------------------------------------------
*/

// Halaman checkout
Route::get('/checkout', [paymentController::class, 'checkout'])
    ->middleware('auth')
    ->name('cart.checkout');

// Proses pembayaran (ambil Snap Token)
Route::post('/process-payment', [paymentController::class, 'processPayment'])
    ->middleware('auth')
    ->name('payment');

// Callback Midtrans
Route::post('/checkout/callback', [paymentController::class, 'callback'])
    ->name('payment.callback');

/*
|--------------------------------------------------------------------------
| Invoice & Riwayat Order
|--------------------------------------------------------------------------
*/

Route::get('/dashboard/invoice/{order_id}', [InvoiceController::class, 'show'])
    ->name('invoice.show');


Route::get('/user/order', [UserController::class, 'order'])->middleware('auth:web')->name('user.transaksi');

// Logout user
Route::post('/logout', [AuthController::class, 'userLogout'])->name('user.logout');
