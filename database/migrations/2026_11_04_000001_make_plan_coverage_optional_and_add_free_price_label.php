<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('coverage_sqm', 6, 2)->nullable()->default(null)->change();
            $table->string('free_price_label', 100)->nullable()->after('coverage_sqm');
        });
    }

    public function down(): void
    {
        DB::table('plans')->whereNull('coverage_sqm')->update(['coverage_sqm' => 0]);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('free_price_label');
            $table->decimal('coverage_sqm', 6, 2)->default(0)->nullable(false)->change();
        });
    }
};
