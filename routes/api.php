<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\XenditCallbackController;

// Pastikan ini ada di file routes/api.php
Route::post('/xendit/callback', [XenditCallbackController::class, 'handle']);