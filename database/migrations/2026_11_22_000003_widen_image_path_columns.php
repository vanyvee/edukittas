<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A stored upload path looks like "schools/logos/<40-char-hash>.png" (about 57 chars).
// schools.logo was 30, schools.pix 50 and users.passport 20, so uploads would not fit.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('logo', 120)->nullable()->change();
            $table->string('pix', 120)->nullable()->change();   // keeps its unique index
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('passport', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('logo', 30)->nullable()->change();
            $table->string('pix', 50)->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('passport', 20)->nullable()->change();
        });
    }
};
