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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // Jika tiap cabang punya menu beda, tambahkan branch_id
            // Jika menu sama untuk semua cabang, branch_id bisa nullable
            $table->string('name'); // Contoh: "Makanan Utama", "Minuman", "Snack"
            $table->string('slug')->unique(); // Untuk URL friendly, contoh: "makanan-utama"
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
