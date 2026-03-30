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
        $menus = Menu::with('branches')->get();
        $tables = Table::all();
        $branches = Branch::all();

        if ($menus->isEmpty() || $tables->isEmpty() || $branches->isEmpty()) {
            $this->command->warn('OrderSeeder skipped karena data dasar belum lengkap.');
            return;
        }

        $taxPercentage = 11.00;
        $paymentMethods = ['QRIS', 'CC', 'Debit', 'Cash'];
        $paymentTypes = ['Kasir', 'Online'];
        $itemNotes = ['Tanpa bawang', 'Extra pedas', null];

        // COUNTER UTAMA
        $maxPendingOrders = 4;
        $pendingCount = 0;

        // Kita mulai dari 2 bulan lalu
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now();

        // Loop setiap hari agar grafik Dashboard Jonathan penuh 30 hari terakhir
        for ($date = $startDate; $date <= $endDate; $date->addDay()) {

            $ordersPerDay = rand(10, 15);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $branch = $branches->random();
                $branchTables = $tables->where('branch_id', $branch->id);
                if ($branchTables->isEmpty()) continue;

                $branchMenus = $menus->filter(fn($m) => $m->branches->contains('id', $branch->id));
                if ($branchMenus->isEmpty()) continue;

                $orderTime = $date->copy()->addSeconds(rand(0, 86399));

                // LOGIKA STATUS: 
                // Jika counter belum sampai 4 DAN hari ini adalah hari ini (Current Date), jadikan pending.
                // Selain itu, semuanya LANGSUNG served & paid.
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

                $orderSubtotal = 0;
                $itemsToCreate = [];
                $itemCount = rand(1, 3);

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
                        // PAKSA STATUS DI SINI
                        'status' => $isPendingThisOrder ? 'pending' : 'served',
                    ];
                    $orderSubtotal += $sub;
                }

                $taxAmount = (int) round($orderSubtotal * $taxPercentage / 100);

                $order = Order::create([
                    'branch_id' => $branch->id,
                    'table_id' => $branchTables->random()->id,
                    'order_number' => 'QRS-' . $orderTime->format('Ymd') . '-' . strtoupper(Str::random(5)),
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
