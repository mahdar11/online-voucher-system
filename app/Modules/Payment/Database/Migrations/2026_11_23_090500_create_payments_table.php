<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 64)->unique(); // dikirim ke Xendit
            $table->string('xendit_invoice_id', 64)->nullable()->unique();
            $table->text('invoice_url')->nullable();
            $table->string('payment_method', 50)->nullable(); // contoh: QR_CODE, BANK_TRANSFER, EWALLET
            $table->string('payment_channel', 50)->nullable(); // contoh: QRIS, BCA, OVO
            $table->unsignedBigInteger('amount'); // rupiah
            $table->string('status', 20)->default('pending');
            $table->jsonb('payload')->nullable(); // callback mentah dari Xendit
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
