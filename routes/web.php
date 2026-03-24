<?php

use App\Models\Table;
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

// --- ROUTE TAMU (Non-Auth / Publik) ---
// 1. Jalur masuk dari Scan QR
Route::get('/s/{token}', function ($token) {
    // Cari berdasarkan qr_token
    $table = \App\Models\Table::where('qr_token', $token)->first();

    if (!$table) {
        dd([
            'status' => 'Token Tidak Ditemukan di DB',
            'token_dari_url' => $token,
            'semua_token_di_db' => \App\Models\Table::pluck('qr_token')->toArray()
        ]);
        return redirect()->route('invalid-access');
    }

    session([
        'customer_table_id' => $table->id,
        'customer_branch_id' => $table->branch_id,
        'customer_table_name' => $table->name,
    ]);

    return redirect()->route('menu.display');
})->name('order.scan');

Route::middleware(['check.table.session'])->group(function () {
    Route::livewire('/menu', 'pages::guest.menu')->name('menu.display');
});

Route::get('/invalid-scan', function () {
    return view('pages.errors.invalid-scan');
})->name('invalid-access');

require __DIR__ . '/settings.php';
