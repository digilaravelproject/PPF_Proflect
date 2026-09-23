<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('warranty_codes', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->index(['subscription_id', 'is_active', 'used_at']);
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->date('available_date')->nullable()->after('booking_date');
            $table->decimal('model_coverage_sqm', 5, 2)->nullable()->after('panel_details');
        });
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->decimal('coverage_sqm', 5, 2)->default(5)->after('photo_path');
        });

        DB::table('vehicle_models')->update(['coverage_sqm' => 5]);
        DB::table('plans')->update(['currency' => 'AUD']);
        Schema::table('plans', fn (Blueprint $table) => $table->string('currency', 3)->default('AUD')->change());
        Schema::table('payments', fn (Blueprint $table) => $table->string('currency', 3)->default('AUD')->change());
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->string('currency', 3)->default('USD')->change());
        Schema::table('plans', fn (Blueprint $table) => $table->string('currency', 3)->default('USD')->change());
        Schema::table('vehicle_models', fn (Blueprint $table) => $table->dropColumn('coverage_sqm'));
        Schema::table('claims', fn (Blueprint $table) => $table->dropColumn(['available_date', 'model_coverage_sqm']));
        Schema::table('warranty_codes', function (Blueprint $table) {
            $table->dropIndex(['subscription_id', 'is_active', 'used_at']);
            $table->dropConstrainedForeignId('subscription_id');
        });
    }
};
