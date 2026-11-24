<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = guest
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20)->nullable();
            $table->unsignedBigInteger('subtotal'); // rupiah
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('customer_email'); // riwayat guest
            $table->index(['status', 'expires_at']); // job pembatalan pesanan kedaluwarsa
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
