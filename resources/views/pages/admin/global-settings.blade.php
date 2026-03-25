<?php

use Livewire\Component;
use App\Models\GlobalSetting;
use Illuminate\Support\Facades\Cache;

new class extends Component
{
    public $settings = [];

    public function mount()
    {
        // Ambil semua setting dan masukkan ke array agar mudah di-bind
        $this->settings = GlobalSetting::all()->pluck('value', 'key')->toArray();
    }

    public function updateSetting($key, $value)
    {
        GlobalSetting::where('key', $key)->update(['value' => $value]);
        $this->settings[$key] = $value;
        Cache::forget("settings_" . $key);
        $this->settings[$key] = $value;
        
        $this->dispatch('toast', type: 'success', text: 'Setting updated successfully.');
    }

    public function render()
    {
        return $this->view()->title('Global System Settings');
    }
};
?>

<div>
    <x-header header="Global Settings" description="Setting will be applied to all branch" />
    <div class="p-6 max-w-6xl mx-auto">
        <h2 class="text-2xl font-black mb-6 text-gray-800">KONFIGURASI SISTEM</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-700">Alur Pesanan</h3>
                </div>

                <div class="flex gap-2">
                    <button wire:click="updateSetting('order_workflow', 'pay_first')"
                        class="flex-1 py-2 rounded-xl font-bold text-sm transition {{ $settings['order_workflow'] == 'pay_first' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                        PAY FIRST
                    </button>
                    <button wire:click="updateSetting('order_workflow', 'serve_first')"
                        class="flex-1 py-2 rounded-xl font-bold text-sm transition {{ $settings['order_workflow'] == 'serve_first' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                        SERVE FIRST
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-3 italic">
                    {{ $settings['order_workflow'] == 'pay_first' ? '*Pelanggan bayar dulu baru masak.' : '*Masak dulu
                    baru bayar di akhir.' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-gray-700 mb-1">Pajak Restoran (PB1)</h3>
                <p class="text-xs text-gray-400 mb-4">Persentase yang ditambahkan ke total.</p>

                <div class="relative">
                    <input type="number" wire:change="updateSetting('tax_percentage', $event.target.value)"
                        value="{{ $settings['tax_percentage'] }}"
                        class="w-full bg-gray-50 border-none rounded-xl font-bold text-lg focus:ring-orange-500">
                    <span class="absolute right-4 top-2 text-gray-400 font-bold">%</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-gray-700 mb-1">Target Waktu Dapur</h3>
                <p class="text-xs text-gray-400 mb-4">Menit sebelum pesanan berubah merah.</p>

                <div class="flex items-center gap-4">
                    <input type="range" min="5" max="60" step="5"
                        wire:change="updateSetting('tat_kitchen', $event.target.value)"
                        value="{{ $settings['tat_kitchen'] }}" class="flex-1 accent-orange-500">
                    <span class="font-black text-xl text-orange-600 w-12">{{ $settings['tat_kitchen'] }}m</span>
                </div>
            </div>
        </div>

        @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2000)"
            class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded-2xl shadow-xl font-bold">
            {{ session('message') }}
        </div>
        @endif
    </div>
</div>