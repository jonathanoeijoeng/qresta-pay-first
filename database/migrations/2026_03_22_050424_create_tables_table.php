<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('number'); // Contoh: "01", "A1", atau "VIP-01"
            $table->integer('capacity')->default(4); // Kapasitas kursi

            // Status meja untuk dashboard waitress
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');

            // Token unik untuk QR Code (untuk keamanan agar tidak mudah ditebak)
            $table->string('qr_token')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
