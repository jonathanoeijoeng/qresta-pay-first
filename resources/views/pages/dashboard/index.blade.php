<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $lastUpdated;

    public function mount()
    {
        $this->lastUpdated = now()->format('H:i:s');
        $user = auth()->user();

        // Cek Role Kitchen
        if ($user->hasRole('kitchen')) {
            return $this->redirectRoute('kitchen.index');
        }

        // Cek Role Cashier
        if ($user->hasRole('cashier')) {
            return $this->redirectRoute('cashier.index');
        }
    }

    public function refreshDashboard()
    {
        // Livewire secara otomatis akan menjalankan ulang query di render()
        // saat method ini dipanggil.
        $this->lastUpdated = now()->format('H:i:s');

        // Opsional: Berikan notifikasi kecil
        session()->flash('status', 'Data berhasil diperbarui.');
    }

    public function getTableDurationData()
    {
        $user = auth()->user();
        $isPusat = is_null($user->branch_id);

        $query = Order::where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(30));

        if (!$isPusat) {
            $query->where('branch_id', $user->branch_id);
        }

        // Logic khusus PostgreSQL:
        // (EXTRACT(EPOCH FROM (paid_at - created_at)) / 60)
        $avgDuration = $query->selectRaw('AVG(EXTRACT(EPOCH FROM (paid_at - created_at)) / 60) as avg_minutes')->first()->avg_minutes;
        return round($avgDuration ?? 0, 0);
    }

    public function getSalesData()
    {
        $user = auth()->user();
        $isPusat = is_null($user->branch_id);

        // 1. Query Dasar
        $query = Order::where('payment_status', 'paid')->where('paid_at', '>=', now()->subDays(30));

        if (!$isPusat) {
            $query->where('branch_id', $user->branch_id);
        }

        $salesData = $query->selectRaw('DATE(paid_at) as date, branch_id, SUM(total_amount) as total')->groupBy('date', 'branch_id')->orderBy('date')->get();

        $labels = $salesData->pluck('date')->unique()->values();

        if ($isPusat) {
            // --- LOGIC PUSAT (Tetap menggunakan Collection agar bisa push & values) ---
            $branches = Branch::all();
            $series = $branches->map(function ($branch) use ($labels, $salesData) {
                return [
                    'name' => $branch->name,
                    'type' => 'column',
                    'data' => $labels->map(fn($d) => (int) ($salesData->where('date', $d)->where('branch_id', $branch->id)->first()->total ?? 0))->toArray(),
                ];
            });

            $totalData = $labels->map(fn($d) => (int) $salesData->where('date', $d)->sum('total'))->toArray();
            $avgValue = count($totalData) > 0 ? array_sum($totalData) / count($totalData) : 0;

            $series->push(['name' => 'Total', 'type' => 'line', 'data' => $totalData]);
            $series->push(['name' => 'Rata-rata', 'type' => 'line', 'data' => array_fill(0, count($totalData), round($avgValue, 0))]);

            $finalSeries = $series->values()->toArray();
        } else {
            // --- LOGIC CABANG ---
            $finalSeries = [
                [
                    'name' => 'Penjualan Cabang Anda',
                    'type' => 'area', // Ganti dari 'line' ke 'area'
                    'data' => $labels->map(fn($d) => (int) ($salesData->where('date', $d)->first()->total ?? 0))->toArray(),
                ],
            ];
        }

        return [
            'labels' => $labels->toArray(),
            'series' => $finalSeries, // Langsung kirim hasil akhirnya
            'isPusat' => $isPusat,
        ];
    }

    public function getStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Total Revenue Hari Ini (Hanya yang sudah PAID)
        $todayRevenue = Order::whereDate('created_at', $today)->where('payment_status', 'paid')->sum('total_amount');

        // Penjualan kemarin
        $yesterdayRevenue = Order::whereDate('created_at', Carbon::yesterday())->where('payment_status', 'paid')->sum('total_amount');

        // Hitung Persentase Perubahan
        $percentageChange = 0;
        if ($yesterdayRevenue > 0) {
            $percentageChange = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        } elseif ($todayRevenue > 0) {
            $percentageChange = 100; // Jika kemarin 0 dan hari ini ada penjualan
        }

        $isUp = $percentageChange >= 0;

        // 2. Total Orders Hari Ini (Semua kecuali yang cancelled)
        $todayOrders = Order::whereDate('created_at', $today)->where('status', '!=', 'cancelled')->count();

        // 3. Breakdown Order (Online vs Kasir)
        $onlineOrders = Order::whereDate('created_at', $today)->where('payment_type', 'Online')->count();
        $cashierOrders = $todayOrders - $onlineOrders;

        // 4. Average Order Value (AOV)
        // Cegah division by zero jika belum ada order
        $aov = $todayOrders > 0 ? $todayRevenue / $todayOrders : 0;

        // 5. Pending Payments (Meja yang sudah pesan tapi belum bayar)
        $pendingCount = Order::where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')->count();

        // 6. Perbandingan dengan Kemarin (Untuk indikator % naik/turun)
        $yesterdayRevenue = Order::whereDate('created_at', $yesterday)->where('payment_status', 'paid')->sum('total_amount');

        $revenueGrowth = 0;
        if ($yesterdayRevenue > 0) {
            $revenueGrowth = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        }

        return compact('todayRevenue', 'todayOrders', 'onlineOrders', 'cashierOrders', 'aov', 'pendingCount', 'revenueGrowth', 'percentageChange', 'isUp', 'yesterdayRevenue');
    }

    public function render()
    {
        return $this->view([
            'chartData' => $this->getSalesData(),
            'stats' => $this->getStats(),
            'tableDurationData' => $this->getTableDurationData(),
        ]);
    }
};
?>

<div>
    <x-header header="Dashboard"
        description="Real-time visualization of branch sales performance and table service efficiency." />
    <div class="flex gap-3 items-center justify-end mb-4">
        <span class="text-xs text-gray-500">
            Terakhir diperbarui: <span class="font-semibold">{{ $lastUpdated }}</span>
        </span>
        <button wire:click="refreshDashboard" wire:loading.class="animate-spin"
            class="p-2 bg-brand-600 text-white rounded-lg cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-auto" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                    clip-rule="evenodd" />
            </svg>
        </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Total Revenue (Hari Ini)</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">
                        IDR {{ number_format($stats['todayRevenue'], 0, '.', ',') }}
                    </h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="{{ $stats['isUp'] ? 'text-green-500' : 'text-red-500' }} font-semibold">
                    {{ $stats['isUp'] ? '+ ' : '' }}{{ number_format($stats['percentageChange'], 1) }}%
                </span>
                <span class="text-gray-400 ml-2">vs kemarin</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['todayOrders'] }}</h3>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-400">
                {{ $stats['onlineOrders'] }} via Online, {{ $stats['cashierOrders'] }} via Kasir
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">AOV (Avg. Spend)</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">
                        IDR {{ number_format($stats['aov'], 0, '.', ',') }}
                    </h3>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-400 mt-3">Rata-rata durasi per meja: <span
                    class="font-semibold">{{ $tableDurationData }}</span> menit</p>
            <div class="mt-2 text-sm text-gray-400">Rata-rata pengeluaran/meja</div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Pending Payments</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $stats['pendingCount'] }} Meja</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 text-sm text-red-400 font-medium">Segera follow-up meja ini!</div>
        </div>
    </div>
    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-800 text-lg">Tren Pendapatan (30 Hari Terakhir)</h3>
        </div>

        <div wire:ignore>
            <div id="salesChart" class="min-h-[320px]"></div>
        </div>
    </div>

    <script>
        window.salesChartInstance = window.salesChartInstance || null;

        function renderDynamicChart() {
            const container = document.querySelector('#salesChart');
            if (!container || typeof ApexCharts === 'undefined') return;
            if (window.salesChartInstance) window.salesChartInstance.destroy();

            // 1. Ambil data asli dari server (30 hari)
            const serverData = @json($chartData);
            let categories = serverData.labels;
            let seriesData = JSON.parse(JSON.stringify(serverData.series)); // Deep clone agar data asli tidak rusak

            // 2. DETEKSI MOBILE: Jika lebar layar < 768px, ambil 5 data terakhir
            const isMobile = window.innerWidth < 768;
            if (isMobile) {
                const limit = 5;
                // Potong label tanggal
                categories = categories.slice(-limit);

                // Potong data di setiap series (Cabang, Total, Rata-rata)
                seriesData.forEach(s => {
                    s.data = s.data.slice(-limit);
                });
            }

            const isPusat = serverData.isPusat;
            const totalSeries = seriesData.length;

            const options = {
                series: seriesData,
                chart: {
                    height: isMobile ? 300 : 400, // Lebih pendek di mobile agar hemat space
                    type: isPusat ? 'line' : 'area',
                    stacked: isPusat,
                    toolbar: {
                        show: !isMobile
                    } // Sembunyikan toolbar di mobile agar bersih
                },
                // ... (Pengaturan stroke, fill, dan markers tetap sama seperti sebelumnya)
                stroke: {
                    width: isPusat ? [...Array(totalSeries - 2).fill(0), 4, 2] : [3],
                    curve: 'smooth',
                    dashArray: isPusat ? [...Array(totalSeries - 1).fill(0), 8] : [0]
                },
                markers: {
                    size: isPusat ? [0, 0, 0, 7, 0] : [7], // Beri titik pada garis untuk user cabang
                    strokeWidth: 2,
                    hover: {
                        size: 8
                    }
                },
                dataLabels: {
                    enabled: false
                },
                colors: isPusat ? ['#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#64748b'] : [
                    '#0ea5e9'
                ],
                xaxis: {
                    type: 'datetime',
                    categories: categories,
                    tickAmount: isMobile ? 4 : 8, // Batasi jumlah label di sumbu X agar tidak tumpang tindih
                    labels: {
                        format: 'dd MMM',
                        style: {
                            fontSize: isMobile ? '10px' : '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            if (val >= 1000000) return 'IDR ' + (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val;
                        }
                    }
                },
                fill: {
                    // Jika pusat: solid (untuk bar). Jika cabang: gradient (untuk area)
                    type: isPusat ? 'solid' : 'gradient',
                    opacity: isPusat ? [...Array(totalSeries - 2).fill(0.4), 1, 1] : [0.6],
                    gradient: {
                        shadeIntensity: 1,
                        inverseColors: false,
                        opacityFrom: 0.6, // Area bawah garis mulai dari 60%
                        opacityTo: 0.05, // Memudar hampir transparan di dasar (0.05)
                        stops: [20, 100] // Gradien mulai memudar setelah 20% ketinggian
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: (val) => "IDR " + val.toLocaleString('en-US')
                    }
                },
                legend: {
                    position: 'top',
                    fontSize: isMobile ? '10px' : '13px',
                    offsetY: isMobile ? 0 : 10
                }
            };

            window.salesChartInstance = new ApexCharts(container, options);
            window.salesChartInstance.render();
        }

        // Jalankan saat load, navigasi, dan resize (opsional)
        document.addEventListener('livewire:navigated', renderDynamicChart);
        window.addEventListener('resize', _.debounce(renderDynamicChart,
            200)); // Gunakan debounce agar tidak berat saat resize
    </script>
</div>
