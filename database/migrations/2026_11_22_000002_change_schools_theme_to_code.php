<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// schools.theme: enum('a','b','c')  ->  themes.code
// Old a/b/c keep the look they had in [code].vue: a=default, b=theme1, c=theme2.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('theme', 30)->default('default')->change();
        });

        DB::table('schools')->where('theme', 'a')->update(['theme' => 'default']);
        DB::table('schools')->where('theme', 'b')->update(['theme' => 'theme1']);
        DB::table('schools')->where('theme', 'c')->update(['theme' => 'theme2']);

        Schema::table('schools', function (Blueprint $table) {
            $table->foreign('theme')->references('code')->on('themes')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['theme']);
        });

        DB::table('schools')->where('theme', 'default')->update(['theme' => 'a']);
        DB::table('schools')->where('theme', 'theme1')->update(['theme' => 'b']);
        DB::table('schools')->where('theme', 'theme2')->update(['theme' => 'c']);
        DB::table('schools')->whereNotIn('theme', ['a', 'b', 'c'])->update(['theme' => 'a']);

        Schema::table('schools', function (Blueprint $table) {
            $table->enum('theme', ['a', 'b', 'c'])->default('a')->change();
        });
    }
};
