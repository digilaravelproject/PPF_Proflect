<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_codes', function (Blueprint $table) {
            $table->foreignId('ppf_variant_id')->nullable()->after('subscription_id')->constrained('ppf_variants')->nullOnDelete();
        });

        Schema::table('vehicle_model_panels', function (Blueprint $table) {
            $table->decimal('area_limit_sqm', 8, 2)->nullable()->after('name');
        });

        DB::table('vehicle_model_panels')->whereNull('area_limit_sqm')->update(['area_limit_sqm' => DB::raw('COALESCE(max_sqm, 0)')]);
    }

    public function down(): void
    {
        Schema::table('vehicle_model_panels', fn (Blueprint $table) => $table->dropColumn('area_limit_sqm'));
        Schema::table('warranty_codes', fn (Blueprint $table) => $table->dropConstrainedForeignId('ppf_variant_id'));
    }
};
