<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_code_settings', function (Blueprint $table) {
            $table->boolean('qr_access_enabled')->default(true)->after('upgrade_window_months');
        });
    }

    public function down(): void
    {
        Schema::table('warranty_code_settings', fn (Blueprint $table) => $table->dropColumn('qr_access_enabled'));
    }
};
