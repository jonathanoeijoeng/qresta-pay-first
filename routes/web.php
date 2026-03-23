<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Admin Menu
Route::middleware(['auth', 'role:super_admin|admin_cabang'])->group(function () {
    // Cara yang benar: Berikan class secara langsung
    Route::livewire('/admin/menu-management', 'pages::admin.menu-management')->name('admin.menu-management');
    Route::livewire('/admin/branch-menu-management', 'pages::admin.branch-menu-management')->name('admin.branch-menu-management');
    Route::livewire('/admin/user-management', 'pages::admin.user-management')->name('admin.user-management');
});


require __DIR__ . '/settings.php';
