<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('vehicle_make')->nullable()->after('subscription_id');
            $table->string('vehicle_model')->nullable()->after('vehicle_make');
            $table->string('registration_number', 30)->nullable()->after('vehicle_model');
            $table->unsignedSmallInteger('vehicle_year')->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('claims', fn (Blueprint $table) => $table->dropColumn(['vehicle_make', 'vehicle_model', 'registration_number', 'vehicle_year']));
    }
};
