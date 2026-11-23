<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory tidak lagi bergantung pada tabel milik Ordering (order_items).
 * Reservasi cukup ditandai dengan referensi teks (nomor invoice) tanpa foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_item_id');
            $table->string('reservation_ref', 40)->nullable()->after('status');
            $table->index('reservation_ref');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['reservation_ref']);
            $table->dropColumn('reservation_ref');
            $table->foreignId('order_item_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });
    }
};
