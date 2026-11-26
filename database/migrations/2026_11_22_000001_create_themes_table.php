<?php

use Database\Seeders\ThemeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Registry of the colour themes a school can pick. Colours themselves live in
// the frontend (App.vue [data-theme="<code>"] blocks); `code` must match them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('code', 30)->primary();
            $table->string('name', 60);
            $table->string('description', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });

        // Rows must exist before schools.theme can reference them.
        (new ThemeSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
