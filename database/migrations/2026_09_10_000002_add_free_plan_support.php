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
            $table->unsignedSmallInteger('duration_days')->nullable()->after('duration_years');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->unsignedBigInteger('payment_id')->nullable()->change();
            $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
        });

        DB::table('plans')->updateOrInsert(
            ['slug' => 'free'],
            [
                'name' => 'Free Plan',
                'description' => 'Try Proflect protection free for 15 days.',
                'price' => 0,
                'currency' => 'USD',
                'duration_years' => 0,
                'duration_days' => 15,
                'coverage_sqm' => 5,
                'features' => json_encode(['15 days of protection', 'No payment required', 'Cancel automatically at trial end']),
                'accent' => 'black',
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'free')->where('price', 0)->delete();

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->unsignedBigInteger('payment_id')->nullable(false)->change();
            $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
        });

        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('duration_days'));
    }
};
