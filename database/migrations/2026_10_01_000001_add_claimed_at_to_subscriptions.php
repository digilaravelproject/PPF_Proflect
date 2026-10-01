<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('claimed_at')->nullable()->after('ends_at')->index();
        });

        DB::table('claims')
            ->select('subscription_id', DB::raw('MIN(created_at) as first_claimed_at'))
            ->groupBy('subscription_id')
            ->orderBy('subscription_id')
            ->get()
            ->each(fn ($claim) => DB::table('subscriptions')
                ->where('id', $claim->subscription_id)
                ->update(['claimed_at' => $claim->first_claimed_at]));
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('claimed_at');
        });
    }
};
