<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Events\OrderUpdated;
use App\Events\OrderSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Verifikasi Token Webhook (Sangat Penting!)
        $callbackToken = config('services.xendit.webhook_token');
        
        if ($request->header('x-callback-token') !== $callbackToken) {
            Log::warning('Xendit Callback: Unauthorized attempt with invalid token.');
            return response()->json(['message' => 'Invalid Callback Token'], 403);
        }

        $payload = $request->all();
        
        // Log untuk memantau di terminal Intel NUC (storage/logs/laravel.log)
        Log::info('Xendit Callback Received:', $payload);

        // 2. Ambil data utama dari payload Xendit
        $orderNumber = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null; // Biasanya 'PAID' atau 'SETTLED'

        if (!$orderNumber) {
            return response()->json(['message' => 'Order number not found in payload'], 400);
        }

        // 3. Cari Order di Database
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            Log::error("Xendit Callback: Order #{$orderNumber} not found in database.");
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 4. Proses Jika Pembayaran Berhasil
        if ($status === 'PAID' || $status === 'SETTLED') {
            
            // Cek agar tidak memproses dua kali (Idempotency)
            if ($order->payment_status !== 'paid') {
                
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at'        => now(),
                    'payment_method' => $payload['payment_method'] ?? 'Xendit Online',
                ]);

                // 5. Broadcast ke Reverb/Echo (Agar UI Tamu di HP otomatis berubah)
                // Ini akan memicu listener "refreshStatus" di Livewire Jonathan
                // event(new OrderUpdated($order));
                broadcast(new OrderUpdated($order))->toOthers();
                broadcast(new OrderSent($order))->toOthers(); 

                Log::info("Xendit Callback: Order #{$orderNumber} marked as PAID.");
            }
        }

        // Beri respon 200 agar Xendit berhenti mengirim ulang callback
        return response()->json(['status' => 'success', 'message' => 'Callback processed']);
    }
}