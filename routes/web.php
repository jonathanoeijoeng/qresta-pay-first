<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('admin/menu-management', 'pages::admin.menu-management')->name('admin.menu-management');
});


require __DIR__ . '/settings.php';
