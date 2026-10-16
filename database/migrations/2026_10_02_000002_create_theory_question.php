<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theory_questions', function (Blueprint $table) {
            $table->id();
            $table->unsigndBigInteger('parent_id')->nullable();
            $table->string('label',5);
            $table->text('content');    
            $table->boolean('type')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theory_questions');
    }
};
