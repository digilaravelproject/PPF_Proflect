<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_codes', function (Blueprint $table) {
            $table->unsignedTinyInteger('validity_months')->default(1)->after('is_active');
            $table->timestamp('activated_at')->nullable()->after('validity_months');
            $table->timestamp('expires_at')->nullable()->after('activated_at')->index();
        });

        // Remove the unused pool created by the previous automatic-code workflow.
        DB::table('warranty_codes')
            ->whereNull('used_at')
            ->whereNull('subscription_id')
            ->where('code', 'like', 'CLM-%')
            ->delete();
    }

    public function down(): void
    {
        Schema::table('warranty_codes', function (Blueprint $table) {
            $table->dropColumn(['validity_months', 'activated_at', 'expires_at']);
        });
    }
};
