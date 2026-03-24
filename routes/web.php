<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'can:qr-code'])->group(function () {
    Route::livewire('/qr-code', 'pages::qr-code')->name('qr-code');
});

Route::get('/order', function () {
    return view('order');
})->name('order.index');

Route::middleware(['auth', 'role:super_admin|admin_cabang'])->group(function () {
    Route::livewire('/admin/menu-management', 'pages::admin.menu-management')->name('admin.menu-management');
    Route::livewire('/admin/branch-menu-management', 'pages::admin.branch-menu-management')->name('admin.branch-menu-management');
    Route::livewire('/admin/user-management', 'pages::admin.user-management')->name('admin.user-management');
    Route::livewire('/admin/role-permission', 'pages::admin.role-permission')->name('admin.role-permission');
});

require __DIR__.'/settings.php';
