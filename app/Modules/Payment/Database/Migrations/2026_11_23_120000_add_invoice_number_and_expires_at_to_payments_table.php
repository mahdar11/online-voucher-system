<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Nomor invoice pesanan, agar modul Payment tidak perlu membaca tabel orders.
            $table->string('invoice_number', 40)->default('')->after('order_id');
            // Batas berlaku invoice Xendit; invoice yang masih berlaku dipakai ulang.
            $table->timestamp('expires_at')->nullable()->after('paid_at');

            $table->index(['invoice_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_number', 'status']);
            $table->dropColumn(['invoice_number', 'expires_at']);
        });
    }
};
