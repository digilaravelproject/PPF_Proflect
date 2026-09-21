<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->index('vehicle_make_id', 'vehicle_models_make_id_index');
            $table->dropUnique('vehicle_models_vehicle_make_id_name_unique');
            $table->string('kind', 20)->default('car')->after('name');
            $table->string('catalog_key')->nullable()->unique()->after('kind');
            $table->string('body_types')->nullable()->after('catalog_key');
            $table->unsignedSmallInteger('year_start')->nullable()->after('body_types');
            $table->unsignedSmallInteger('year_end')->nullable()->after('year_start');
            $table->unique(['vehicle_make_id', 'kind', 'name']);
        });
        Schema::table('vehicle_model_panels', function (Blueprint $table) {
            $table->decimal('min_sqm', 8, 2)->nullable()->change();
            $table->decimal('max_sqm', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_model_panels', function (Blueprint $table) {
            $table->decimal('min_sqm', 8, 2)->nullable(false)->change();
            $table->decimal('max_sqm', 8, 2)->nullable(false)->change();
        });
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropUnique(['vehicle_make_id', 'kind', 'name']);
            $table->dropColumn(['kind', 'catalog_key', 'body_types', 'year_start', 'year_end']);
            $table->unique(['vehicle_make_id', 'name']);
            $table->dropIndex('vehicle_models_make_id_index');
        });
    }
};
