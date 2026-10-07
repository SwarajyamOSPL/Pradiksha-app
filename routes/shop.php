<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart', [CartController::class, 'store'])->name('cart.add');
    Route::patch('cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::livewire('checkout', 'pages::shop.checkout')->name('checkout');
    Route::livewire('packages/{package:slug}/checkout', 'pages::shop.package-checkout')->name('packages.checkout');

    Route::livewire('orders', 'pages::shop.orders.index')->name('orders.index');
    Route::livewire('orders/{order}', 'pages::shop.orders.show')->name('orders.show');
});
