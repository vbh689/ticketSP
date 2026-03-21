<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_manager')->default(false)->after('primary_contact_method');
            $table->boolean('is_active')->default(true)->after('is_manager');
        });

        DB::table('users')->update([
            'is_manager' => false,
            'is_active' => true,
        ]);

        DB::table('users')
            ->where('email', 'support.lead@internal.local')
            ->update(['is_manager' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_manager', 'is_active']);
        });
    }
};
