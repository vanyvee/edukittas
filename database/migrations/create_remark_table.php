<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedMediumInteger('school_id'); // the school that wrote it
            $table->text('remark');
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('my_schools');
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
};