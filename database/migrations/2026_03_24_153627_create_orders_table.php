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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();

            // Nomor pesanan unik (Contoh: QRS-20260324-001)
            $table->string('order_number')->unique();

            // Status alur kerja restoran
            $table->enum('status', ['draft', 'pending', 'processing', 'completed', 'cancelled'])->default('pending');

            // Status pembayaran
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_method')->nullable(); // Cash, QRIS, etc.

            $table->bigInteger('total_amount'); // Gunakan integer (IDR) tanpa koma di DB
            $table->bigInteger('tax_amount'); // Gunakan integer (IDR) tanpa koma di DB
            $table->text('notes')->nullable(); // Catatan tambahan dari tamu (e.g. "Tanpa bawang")

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
