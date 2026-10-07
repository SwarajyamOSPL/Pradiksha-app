<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        Route::livewire('products', 'pages::admin.products.index')->name('products.index');
        Route::livewire('products/create', 'pages::admin.products.create')->name('products.create');
        Route::livewire('products/{product}/edit', 'pages::admin.products.edit')->name('products.edit');

        Route::livewire('packages', 'pages::admin.packages.index')->name('packages.index');
        Route::livewire('packages/create', 'pages::admin.packages.create')->name('packages.create');
        Route::livewire('packages/{package}/edit', 'pages::admin.packages.edit')->name('packages.edit');

        Route::livewire('franchise-holders', 'pages::admin.franchise-holders.index')->name('franchise-holders.index');
        Route::livewire('franchise-holders/{user}', 'pages::admin.franchise-holders.show')->name('franchise-holders.show');

        Route::livewire('sales-managers', 'pages::admin.sales-managers.index')->name('sales-managers.index');
        Route::livewire('sales-managers/create', 'pages::admin.sales-managers.create')->name('sales-managers.create');
        Route::livewire('sales-managers/{salesManager}/edit', 'pages::admin.sales-managers.edit')->name('sales-managers.edit');

        Route::livewire('orders', 'pages::admin.orders.index')->name('orders.index');
        Route::livewire('orders/{order}', 'pages::admin.orders.show')->name('orders.show');
    });
