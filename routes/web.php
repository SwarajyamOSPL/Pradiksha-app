<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('packages/{package:slug}', [PackageController::class, 'show'])->name('packages.show');

Route::view('franchise', 'franchise')->name('franchise');

Route::middleware(['auth'])->group(function () {
    Route::view('account/pending', 'pages.account.pending')->name('account.pending');
});

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/shop.php';
require __DIR__.'/sales.php';
