<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_code_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('upgrade_window_months')->default(2)->after('validity_months');
        });
    }

    public function down(): void
    {
        Schema::table('warranty_code_settings', fn (Blueprint $table) => $table->dropColumn('upgrade_window_months'));
    }
};
