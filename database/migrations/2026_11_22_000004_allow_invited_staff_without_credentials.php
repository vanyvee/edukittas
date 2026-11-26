<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A staff member is registered first (inactive, no login) and sets their own username and password
// from the emailed link. So both columns must be allowed to be empty until then. The old '55555'
// default is removed on purpose: an empty password can never log in, a plain '55555' could not either
// but looked like it should.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 20)->nullable()->change();   // keeps its unique index
            $table->string('password', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('username')->update(['username' => DB::raw("CONCAT('user', id)")]);
        DB::table('users')->whereNull('password')->update(['password' => '55555']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 20)->nullable(false)->change();
            $table->string('password', 60)->nullable(false)->default('55555')->change();
        });
    }
};
