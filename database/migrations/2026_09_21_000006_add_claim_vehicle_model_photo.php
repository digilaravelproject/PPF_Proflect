<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('claims', fn (Blueprint $table) => $table->string('vehicle_model_photo_path')->nullable()->after('vehicle_model_id'));
    }

    public function down(): void
    {
        Schema::table('claims', fn (Blueprint $table) => $table->dropColumn('vehicle_model_photo_path'));
    }
};
