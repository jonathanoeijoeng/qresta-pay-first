<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService
{
    /**
     * Mengambil workflow order (A-Z) dengan Cache
     */
    public static function getOrderWorkflow(): string
    {
        return Cache::rememberForever('global_order_workflow', function () {
            return DB::table('global_settings')
                ->where('key', 'order_workflow')
                ->value('value') ?? 'serve_first';
        });
    }

    // app/Services/SettingService.php

    public static function getTaxPercentage(): int
    {
        return (int) Cache::rememberForever('global_tax_percentage', function () {
            return DB::table('global_settings')
                ->where('key', 'tax_percentage')
                ->value('value') ?? 10;
        });
    }

    public static function updateTaxPercentage(int $value): void
    {
        DB::table('global_settings')
            ->where('key', 'tax_percentage')
            ->update(['value' => $value, 'updated_at' => now()]);

        Cache::forget('global_tax_percentage');
    }
}
