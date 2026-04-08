<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data dasar, gunakan keyBy agar akses ID lebih cepat
        $menus = Menu::with('branches')->get();
        $tables = Table::all();
        $branches = Branch::all()->keyBy('id');

        if ($menus->isEmpty() || $tables->isEmpty() || $branches->count() < 3) {
            $this->command->warn('OrderSeeder skipped: Pastikan data Menu, Table, dan minimal 3 Branch sudah ada.');
            return;
        }

        $taxPercentage = 11.00;
        $paymentMethods = ['QRIS', 'CC', 'Debit', 'Cash'];
        $paymentTypes = ['Kasir', 'Online'];
        $itemNotes = ['Tanpa bawang', 'Extra pedas', null];

        // Counter untuk pesanan yang belum selesai (pending)
        $maxPendingOrders = 10;
        $pendingCount = 0;

        // Rentang waktu: 2 bulan lalu sampai sekarang
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now();

        // Loop harian
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {

            // Maksimal 150 order per hari sesuai kesepakatan
            $ordersPerDay = rand(80, 150);

            // Loop pesanan per hari
            for ($i = 0; $i < $ordersPerDay; $i++) {
                
                // --- 1. LOGIKA DISTRIBUSI CABANG (50% : 35% : 15%) ---
                $roll = rand(1, 100);
                if ($roll <= 50) {
                    $targetBranchId = 1; 
                } elseif ($roll <= 85) {
                    $targetBranchId = 2; 
                } else {
                    $targetBranchId = 3; 
                }

                $branch = $branches->get($targetBranchId) ?? $branches->first();

                // --- 2. LOGIKA RELASI (Table & Menu per Cabang) ---
                $branchTables = $tables->where('branch_id', $branch->id);
                if ($branchTables->isEmpty()) continue;

                $branchMenus = $menus->filter(fn($m) => $m->branches->contains('id', $branch->id));
                if ($branchMenus->isEmpty()) continue;

                // Pilih meja secara acak untuk mendapatkan table_id
                $selectedTable = $branchTables->random();

                // --- 3. PENENTUAN NOMOR ORDER UNIK (Format: QRS-Tanggal-Meja-Sequence-Random) ---
                // Menggunakan format: QRS-20260331-007-001-AXB
                $datePart = $date->format('Ymd');
                $tablePart = str_pad($selectedTable->id, 3, '0', STR_PAD_LEFT);
                $sequencePart = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                $randomPart = strtoupper(Str::random(3));

                $orderNumber = "QPF-{$datePart}-{$tablePart}-{$sequencePart}-{$randomPart}";

                $orderTime = $date->copy()->addSeconds(rand(0, 86399));

                // --- 4. LOGIKA STATUS PESANAN ---
                $isPendingThisOrder = false;
                if ($date->isToday() && $pendingCount < $maxPendingOrders) {
                    $isPendingThisOrder = true;
                    $orderStatus = 'processing';
                    $paymentStatus = 'unpaid';
                    $pendingCount++;
                } else {
                    $orderStatus = 'completed-served';
                    $paymentStatus = 'paid';
                }

                // --- 5. LOGIKA ITEM PESANAN & SUB-TOTAL ---
                $orderSubtotal = 0;
                $itemsToCreate = [];
                $itemCount = rand(1, 7);

                for ($j = 0; $j < $itemCount; $j++) {
                    $menu = $branchMenus->random();
                    $price = $menu->branches->firstWhere('id', $branch->id)->pivot->price ?? $menu->base_price;
                    $qty = rand(1, 2);
                    $sub = $price * $qty;

                    $itemsToCreate[] = [
                        'menu_id' => $menu->id,
                        'quantity' => $qty,
                        'price_at_order' => $price,
                        'subtotal' => $sub,
                        'notes' => Arr::random($itemNotes),
                        'status' => $isPendingThisOrder ? 'pending' : 'served',
                    ];
                    $orderSubtotal += $sub;
                }

                $taxAmount = (int) round($orderSubtotal * $taxPercentage / 100);

                // --- 6. SIMPAN DATA ORDER ---
                $order = Order::create([
                    'branch_id' => $branch->id,
                    'table_id' => $selectedTable->id, // Menggunakan ID meja yang sama dengan di order_number
                    'order_number' => $orderNumber,
                    'status' => $orderStatus,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentStatus === 'paid' ? Arr::random($paymentMethods) : null,
                    'payment_type' => $paymentStatus === 'paid' ? Arr::random($paymentTypes) : null,
                    'total_amount' => $orderSubtotal + $taxAmount,
                    'tax_percentage' => $taxPercentage,
                    'tax_amount' => $taxAmount,
                    'confirmed_at' => $orderTime->copy()->subMinutes(rand(5, 20)),
                    'paid_at' => $paymentStatus === 'paid' ? $orderTime : null,
                    'created_at' => $orderTime,
                    'updated_at' => $orderTime,
                ]);

                // --- 7. SIMPAN DATA ORDER ITEMS ---
                foreach ($itemsToCreate as $item) {
                    $item['order_id'] = $order->id;
                    $item['created_at'] = $orderTime;
                    $item['updated_at'] = $orderTime;
                    OrderItem::create($item);
                }
            }
        }
    }
}