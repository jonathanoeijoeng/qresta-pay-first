<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Contoh: 'order_workflow'
            $table->string('value');          // Contoh: 'serve_first'
            $table->timestamps();
        });

        // Insert data default agar aplikasi tidak error saat pertama jalan
        DB::table('global_settings')->insert([
            [
                'key' => 'order_workflow',
                'value' => 'serve_first',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Anda bisa tambah setting lain di sini nanti, misal:
            // ['key' => 'tax_percentage', 'value' => '10'],
        ]);
        DB::table('global_settings')->insert([
            [
                'key' => 'tax_percentage',
                'value' => '10', // Default 10%
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
