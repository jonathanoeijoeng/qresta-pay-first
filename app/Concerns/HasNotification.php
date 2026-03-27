<?php

namespace App\Concerns;

trait HasNotification
{
    /**
     * Mengirim sinyal suara ke browser dan me-refresh data Livewire.
     */
    public function notifyAndRefresh($type = 'default')
    {
        // 1. Kirim event ke browser (untuk ditangkap JS Global Listener)
        $this->dispatch('play-sound', type: $type);

        // 2. Refresh komponen Livewire yang menggunakan trait ini
        $this->dispatch('$refresh');
    }
}
