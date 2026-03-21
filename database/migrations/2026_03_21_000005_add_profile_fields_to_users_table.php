<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
            $table->string('phone')->nullable()->after('username');
            $table->string('department')->nullable()->after('phone');
            $table->string('primary_contact_method')->nullable()->after('department');
        });

        DB::table('users')
            ->select(['id', 'email', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $baseUsername = Str::of($user->email)
                    ->before('@')
                    ->lower()
                    ->replaceMatches('/[^a-z0-9._-]+/', '-')
                    ->trim('-')
                    ->value();

                $username = $baseUsername !== '' ? $baseUsername : 'user-'.$user->id;
                $suffix = 1;

                while (
                    DB::table('users')
                        ->where('username', $username)
                        ->where('id', '!=', $user->id)
                        ->exists()
                ) {
                    $suffix++;
                    $username = "{$baseUsername}-{$suffix}";
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'username' => $username,
                        'department' => 'IT Support',
                        'primary_contact_method' => 'Email',
                    ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'phone',
                'department',
                'primary_contact_method',
            ]);
        });
    }
};
