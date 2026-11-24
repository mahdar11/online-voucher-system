<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_packages', function (Blueprint $table) {
            // Masa aktif dalam hari, dihitung router sejak login pertama. null = tanpa batas.
            $table->unsignedSmallInteger('validity_days')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_packages', function (Blueprint $table) {
            $table->dropColumn('validity_days');
        });
    }
};
