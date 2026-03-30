<?php

use Livewire\Component;
use App\Models\Order;

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

    public function render()
    {
        return $this->view([
            'salesData' => $this->getSalesData(),
        ]);
    }
};
?>

<div>
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
                    colors: ['#0b84ba']
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
