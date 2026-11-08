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
            $table->char('guid', 36)->nullable()->unique();
        });

        DB::table('users')->whereNull('guid')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['guid' => (string) Str::uuid()]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->char('guid', 36)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['guid']);
            $table->dropColumn('guid');
        });
    }
};