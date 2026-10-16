<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_attempt_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cbt_attempt_id')->constrained('cbt_attempts')->cascadeOnDelete();
            $table->foreignId('temp_question_id')->constrained('temp_questions')->cascadeOnDelete();

            // What the student submitted: a string, or a list of strings for multi-select.
            // NULL = left unanswered.
            $table->json('answer')->nullable();

            // Filled in when the attempt is graded.
            $table->boolean('is_correct')->nullable();
            $table->unsignedTinyInteger('marks_awarded')->nullable();

            $table->timestamps();

            // One row per question per attempt. CbtService upserts against this key.
            $table->unique(['cbt_attempt_id', 'temp_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_attempt_answers');
    }
};
