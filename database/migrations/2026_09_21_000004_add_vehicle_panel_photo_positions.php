<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_model_panels', function (Blueprint $table) {
            $table->decimal('photo_x', 5, 2)->nullable();
            $table->decimal('photo_y', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_model_panels', fn (Blueprint $table) => $table->dropColumn(['photo_x', 'photo_y']));
    }
};
