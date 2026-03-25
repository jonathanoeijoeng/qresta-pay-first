<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SettingService;
use Illuminate\Support\Facades\Session;

class LoadOrderEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. PROTEKSI: Cek apakah session meja ada?
        // (Pastikan key session-nya sama dengan yang Anda pakai sebelumnya)
        if (!Session::has('customer_table_id')) {
            // Jika tidak ada session, tendang balik ke halaman scan atau home
            return redirect()->route('scan.error')->with('error', 'Silakan scan QR Code meja kembali.');
        }

        // 2. CONFIGURATION: Jika session aman, baru muat semua settingannya
        config([
            'app.order_workflow' => SettingService::getOrderWorkflow(),
            'app.tax_percentage' => SettingService::getTaxPercentage(),
            'app.active_table_id' => Session::get('customer_table_id'),
        ]);

        return $next($request);
    }
}
