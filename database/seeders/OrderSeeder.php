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
            $this->command->warn('OrderSeeder skipped because Branch, Table, or Menu data is missing.');

            return;
        }

        $startMonth = Carbon::now()->subMonths(2)->startOfMonth();
        $taxPercentage = 11.00;
        $paymentMethods = ['QRIS', 'CC', 'Debit', 'Cash'];
        $paymentTypes = ['Kasir', 'Online'];
        $itemNotes = ['Tanpa bawang', 'Extra pedas', 'Sambal terpisah', 'Extra saos', null, null];
        $orderStatuses = ['completed-served', 'processing', 'pending', 'cancelled'];
        $currentMonthUnpaidRemaining = 8;

        for ($month = 0; $month < 3; $month++) {
            $currentMonth = $startMonth->copy()->addMonths($month);
            $days = range(1, $currentMonth->daysInMonth);
            shuffle($days);
            $activeDays = array_slice($days, 0, rand(15, min(22, $currentMonth->daysInMonth)));
            sort($activeDays);

            foreach ($activeDays as $dayNumber) {
                $date = $currentMonth->copy()->day($dayNumber);
                $ordersPerDay = rand(10, 18);

                for ($orderIndex = 0; $orderIndex < $ordersPerDay; $orderIndex++) {
                    $branch = $branches->random();
                    $branchTables = $tables->where('branch_id', $branch->id);

                    if ($branchTables->isEmpty()) {
                        continue;
                    }

                    $branchMenus = $menus->filter(function ($menu) use ($branch) {
                        return $menu->branches->contains('id', $branch->id);
                    });

                    if ($branchMenus->isEmpty()) {
                        continue;
                    }

                    $orderDate = $date->copy()->addSeconds(rand(0, 86399));
                    $orderStatus = Arr::random($orderStatuses);
                    $isCurrentMonth = $currentMonth->isSameMonth(Carbon::now()) && $currentMonth->isSameYear(Carbon::now());
                    if ($isCurrentMonth) {
                        $paymentStatus = $currentMonthUnpaidRemaining > 0 ? 'unpaid' : 'paid';
                        if ($paymentStatus === 'unpaid') {
                            $currentMonthUnpaidRemaining--;
                        }
                    } else {
                        $paymentStatus = 'paid';
                    }
                    $paymentType = $paymentStatus === 'paid' ? Arr::random($paymentTypes) : null;

                    $items = [];
                    $orderSubtotal = 0;
                    $itemCount = rand(1, 4);

                    for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
                        $menu = $branchMenus->random();
                        $branchPivot = $menu->branches->firstWhere('id', $branch->id)->pivot;
                        $priceAtOrder = $branchPivot->price ?? $menu->base_price;
                        $quantity = rand(1, 3);
                        $subtotal = $priceAtOrder * $quantity;

                        $items[] = [
                            'menu_id' => $menu->id,
                            'quantity' => $quantity,
                            'price_at_order' => $priceAtOrder,
                            'subtotal' => $subtotal,
                            'notes' => Arr::random($itemNotes),
                            'status' => 'pending',
                        ];

                        $orderSubtotal += $subtotal;
                    }

                    $taxAmount = (int) round($orderSubtotal * $taxPercentage / 100);
                    $totalAmount = $orderSubtotal + $taxAmount;
                    $paymentMethod = $paymentStatus === 'paid' ? Arr::random($paymentMethods) : null;
                    $confirmedAt = $paymentStatus === 'paid' ? $orderDate->copy()->subMinutes(rand(2, 30)) : null;
                    $paidAt = $paymentStatus === 'paid' ? $orderDate->copy()->subMinutes(rand(0, 20)) : null;

                    $order = Order::create([
                        'branch_id' => $branch->id,
                        'table_id' => $branchTables->random()->id,
                        'order_number' => 'QRS-'.$orderDate->format('Ymd').'-'.strtoupper(Str::random(5)),
                        'status' => $orderStatus,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'payment_type' => $paymentType,
                        'total_amount' => $totalAmount,
                        'tax_percentage' => $taxPercentage,
                        'tax_amount' => $taxAmount,
                        'notes' => Arr::random($itemNotes),
                        'confirmed_at' => $confirmedAt,
                        'paid_at' => $paidAt,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);

                    foreach ($items as $itemData) {
                        $itemData['order_id'] = $order->id;
                        $itemData['created_at'] = $orderDate;
                        $itemData['updated_at'] = $orderDate;
                        OrderItem::create($itemData);
                    }
                }
            }
        }
    }
}
