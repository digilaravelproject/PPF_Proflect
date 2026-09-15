<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->update(['currency' => 'USD']);
        DB::table('payments')->update(['currency' => 'USD']);
    }

    public function down(): void
    {
        DB::table('plans')->update(['currency' => 'INR']);
        DB::table('payments')->update(['currency' => 'INR']);
    }
};
