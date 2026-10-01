<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_code_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('validity_months')->default(1);
            $table->timestamps();
        });

        $validityMonths = (int) (DB::table('warranty_codes')->latest('id')->value('validity_months') ?? 1);
        $validityMonths = min(12, max(1, $validityMonths));

        DB::table('warranty_code_settings')->insert([
            'id' => 1,
            'validity_months' => $validityMonths,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warranty_codes')->orderBy('id')->eachById(function (object $code) use ($validityMonths): void {
            $values = ['validity_months' => $validityMonths];
            if ($code->activated_at) {
                $values['expires_at'] = Carbon::parse($code->activated_at)->addMonthsNoOverflow($validityMonths);
            }
            DB::table('warranty_codes')->where('id', $code->id)->update($values);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_code_settings');
    }
};
