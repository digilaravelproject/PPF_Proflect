<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('ppf_variants', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->text('description')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); });
        Schema::table('claims', function (Blueprint $table) { $table->foreignId('ppf_variant_id')->nullable()->after('warranty_code_id')->constrained('ppf_variants')->nullOnDelete(); $table->unsignedBigInteger('subscription_id')->nullable()->change(); });
    }
    public function down(): void { Schema::table('claims', function (Blueprint $table) { $table->dropConstrainedForeignId('ppf_variant_id'); $table->unsignedBigInteger('subscription_id')->nullable(false)->change(); }); Schema::dropIfExists('ppf_variants'); }
};
