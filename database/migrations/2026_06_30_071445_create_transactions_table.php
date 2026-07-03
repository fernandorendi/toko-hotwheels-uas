<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // Kode Nota (contoh: HW-20260630-001)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Kasir/User pembuat
            $table->decimal('total_price', 10, 2);        // Total bayar
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};