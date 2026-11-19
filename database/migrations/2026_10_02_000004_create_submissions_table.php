<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_id')->constrained('cbts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('started_at');

            // min(started_at + duration_minutes, deadline) — computed once
            // at start(), stored so the auto-submit job just compares
            // now() against this column, no join back to cbts needed.
            $table->dateTime('personal_expiry');

            // {question_id: {answer: {type, value}, marks_awarded}} —
            // every answer for this attempt, in one place. Autosaved as
            // the student works; graded in place once submitted.
            $table->json('answers')->nullable();

            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'late'])->default('in_progress');
            $table->unsignedSmallInteger('score')->nullable();
            $table->timestamps();

            // One attempt per student per CBT.
            $table->unique(['cbt_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
