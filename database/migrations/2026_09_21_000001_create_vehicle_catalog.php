<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_make_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['vehicle_make_id', 'name']);
        });
        Schema::create('vehicle_model_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->decimal('min_sqm', 8, 2);
            $table->decimal('max_sqm', 8, 2);
            $table->timestamps();
            $table->unique(['vehicle_model_id', 'key']);
        });
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('vehicle_model_id')->nullable()->after('vehicle_model')->constrained()->nullOnDelete();
            $table->json('panel_details')->nullable()->after('panels');
        });
    }

    public function down(): void
    {
        Schema::table('claims', fn (Blueprint $table) => $table->dropConstrainedForeignId('vehicle_model_id'));
        Schema::table('claims', fn (Blueprint $table) => $table->dropColumn('panel_details'));
        Schema::dropIfExists('vehicle_model_panels');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_makes');
    }
};
