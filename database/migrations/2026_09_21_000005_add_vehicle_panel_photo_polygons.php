<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_model_panels', fn (Blueprint $table) => $table->json('photo_polygon')->nullable());
    }

    public function down(): void
    {
        Schema::table('vehicle_model_panels', fn (Blueprint $table) => $table->dropColumn('photo_polygon'));
    }
};
