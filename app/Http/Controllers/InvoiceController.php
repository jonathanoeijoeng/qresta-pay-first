<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;

class InvoiceController extends Controller
{
    public function download($order_number)
    {
        $order = Order::with(['items.menu', 'branch', 'table'])
            ->where('order_number', $order_number)
            ->where('payment_status', 'paid') 
            ->firstOrFail();

        // Logika tambahan: Jika bukan admin/kasir, cek apakah ini meja si tamu (via session)
        if (!auth()->check() && $order->table_id !== session('customer_table_id')) {
            abort(403, 'Akses ditolak.');
        }

        $pdf = Pdf::loadView('pages.pdf.invoice', compact('order'));
        return $pdf->download("Invoice-{$order->order_number}.pdf");
    }
}
