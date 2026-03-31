<?php

use Livewire\Component;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {

    public function getSalesData()
    {
        // Mengambil data 30 hari terakhir
        $sales = Order::where('payment_status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(paid_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = $sales->pluck('total');
        $average = $data->count() ? round($data->avg(), 0) : 0;

        return [
            'labels' => $sales->pluck('date'),
            'data' => $data,
            'average' => $average,
        ];
    }

    public function getStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Total Revenue Hari Ini (Hanya yang sudah PAID)
        $todayRevenue = Order::whereDate('created_at', $today)->where('payment_status', 'paid')->sum('total_amount');

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

        return compact('todayRevenue', 'todayOrders', 'onlineOrders', 'cashierOrders', 'aov', 'pendingCount', 'revenueGrowth');
    }

    public function render()
    {
        return $this->view([
            'salesData' => $this->getSalesData(),
            'stats' => $this->getStats(),
        ]);
    }
};
?>

<div>
    <x-header header="Dashboard" description="Cook fast Serve fast" />
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
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zM12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 font-semibold">+12%</span>
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
            <div class="mt-4 text-sm text-gray-400">Rata-rata pengeluaran/meja</div>
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
        // Gunakan properti window agar tidak error "Identifier already declared" saat navigasi
        window.salesChartInstance = window.salesChartInstance || null;

        function renderSalesChart() {
            const chartContainer = document.querySelector('#salesChart');

            // 1. Validasi dasar
            if (!chartContainer || typeof ApexCharts === 'undefined') {
                return;
            }

            // 2. KRUSIAL: Hancurkan instance lama jika ada sebelum membuat yang baru
            if (window.salesChartInstance) {
                window.salesChartInstance.destroy();
                window.salesChartInstance = null;
            }

            // Siapkan data rata-rata dari PHP
            const averageValue = @json($salesData['average'] ?? 0);
            const dataLength = @json(count($salesData['data'] ?? []));
            const averageSeries = Array(dataLength).fill(averageValue);

            const options = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                series: [{
                    name: 'Pendapatan',
                    data: @json($salesData['data'])
                }, {
                    name: 'Rata-rata',
                    data: averageSeries
                }],
                xaxis: {
                    type: 'datetime',
                    categories: @json($salesData['labels']),
                    tickAmount: 5, // Agar sumbu X tetap lega (muncul ~setiap 6 hari)
                    labels: {
                        datetimeUTC: false,
                        format: 'dd MMM',
                        style: {
                            colors: '#64748b',
                            fontSize: '10px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                colors: ['#0ea5e9', '#f97316'], // Biru untuk Pendapatan, Oranye untuk Rata-rata
                stroke: {
                    curve: 'smooth',
                    width: [3, 2],
                    dashArray: [0, 6] // Garis rata-rata dibuat putus-putus
                },
                markers: {
                    size: [7, 0], // Marker kecil di titik pendapatan saja
                    colors: ['#0b84ba'],
                    hover: {
                        size: undefined,
                        sizeOffset: 2
                        },
                },
                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.7, 0], // Rata-rata tidak pakai fill agar tidak menumpuk
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.2
                    }
                },
                dataLabels: {
                    enabled: false
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            if (value >= 1000000) return 'IDR ' + (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return 'IDR ' + (value / 1000).toFixed(0) + 'K';
                            return 'IDR ' + value;
                        },
                    }
                },
                tooltip: {
                    x: {
                        formatter: function(val) {
                            return new Date(val).toLocaleDateString('id-ID', {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'short'
                            });
                        }
                    }
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    fontSize: '12px',
                    offsetY: 20,
                }
            };

            // 3. Simpan instance ke window
            window.salesChartInstance = new ApexCharts(chartContainer, options);
            window.salesChartInstance.render();
        }

        // Jalankan saat load pertama dan setiap kali navigasi Livewire v4 selesai
        document.addEventListener('livewire:navigated', renderSalesChart);
    </script>
</div>
