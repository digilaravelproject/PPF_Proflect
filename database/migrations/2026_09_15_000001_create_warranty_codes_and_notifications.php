<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('warranty_code_id')->nullable()->after('subscription_id')->unique()->constrained('warranty_codes')->restrictOnDelete();
            $table->date('booking_date')->nullable()->after('reviewed_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key');
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['user_id', 'event_key']);
        });

        $now = now();
        $rows = [];
        for ($index = 0; $index < 100; $index++) {
            do {
                $code = 'CLM-'.$now->format('ymd').'-'.Str::upper(Str::random(6));
            } while (collect($rows)->contains('code', $code));
            $rows[] = ['code' => $code, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('warranty_codes')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notification_events');
        Schema::dropIfExists('notifications');
        Schema::table('claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warranty_code_id');
            $table->dropColumn('booking_date');
        });
        Schema::dropIfExists('warranty_codes');
    }
};
