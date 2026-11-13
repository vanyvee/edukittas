<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
             $table->string('selected',255);
            $table->text('answer_text')->nullable(); // theory questions
            $table->unsignedTinyInteger('marks_awarded')->nullable();
            $table->timestamps();

            // One answer row per question per submission — upsert target,
            // same reasoning as autosaving answers while a student works.
            $table->unique(['submission_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
