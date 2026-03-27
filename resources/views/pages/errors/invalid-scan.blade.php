<x-layouts.guest>
    <div class="min-h-screen flex items-center justify-center bg-zinc-50 p-6">
        <div class="max-w-md w-full text-center space-y-6">
            {{-- Icon Peringatan --}}
            <div class="flex justify-center">
                <div class="p-4 bg-brand-100 rounded-full">
                    <flux:icon.hand-raised class="w-12 h-12 text-brand-500" />
                </div>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-bold text-zinc-900">Akses Ditolak</h1>
                <p class="text-zinc-600">
                    Maaf, Anda harus memindai **QR Code** yang ada di meja restoran untuk melihat menu dan memesan
                    makanan.
                </p>
            </div>

            <div class="pt-4">
                <p class="text-xs text-zinc-400 italic">
                    Jika Anda merasa ini adalah kesalahan, silakan hubungi pelayan kami.
                </p>
            </div>
        </div>
    </div>
</x-layouts.guest>