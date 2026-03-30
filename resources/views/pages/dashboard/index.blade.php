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
        let salesChartInstance;

        function renderSalesChart() {
            const chartContainer = document.querySelector('#salesChart');
            if (!chartContainer || typeof ApexCharts === 'undefined' || salesChartInstance) {
                return;
            }

            const averageSeries = Array(@json(count($salesData['data']))).fill(@json($salesData['average']));

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
                colors: ['#0ea5e9', '#f97316'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2],
                    dashArray: [0, 6]
                },
                markers: {
                    size: [7, 0],
                    colors: '#0973a3'
                },
                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.7, 0],
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                dataLabels: {
                    enabled: false
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            if (value >= 1000000) {
                                return 'IDR ' + (value / 1000000).toFixed(1) + 'M'; // Contoh: IDR 1.5M
                            } else if (value >= 1000) {
                                return 'IDR ' + (value / 1000).toFixed(0) + 'K'; // Contoh: IDR 850K
                            }
                            return 'IDR ' + value;
                        },
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy'
                    } // Tooltip tetap muncul detail tanggalnya
                },
                legend: {
                    show: true,
                    fontSize: '12px',
                    offsetY: 20,
                }
            };

            salesChartInstance = new ApexCharts(chartContainer, options);
            salesChartInstance.render();
        }

        document.addEventListener('DOMContentLoaded', renderSalesChart);
        document.addEventListener('livewire:navigated', renderSalesChart);
    </script>
</div>
