<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('voucher_packages')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('voucher_batches')->nullOnDelete();
            $table->string('code', 64);
            $table->string('password', 64)->nullable();
            $table->string('status', 20)->default('available');
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'code']);
            $table->index(['package_id', 'status']); // ambil stok dengan FOR UPDATE SKIP LOCKED
            $table->index(['status', 'reserved_until']); // lepas reservasi yang kedaluwarsa
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
