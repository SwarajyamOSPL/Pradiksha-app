<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved', 'role:sales_manager'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::livewire('/', 'pages::sales.dashboard')->name('dashboard');
        Route::livewire('franchise-holders/{user}', 'pages::sales.franchise-holders.show')->name('franchise-holders.show');
    });
