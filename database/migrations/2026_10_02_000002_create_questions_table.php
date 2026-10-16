<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            // Tenancy / ownership
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            // Grouping. NULL group = standalone question.
            $table->foreignId('group_id')->nullable()->constrained('question_groups')->cascadeOnDelete();
            // Position inside the group. For gap groups this is the gap index
            // and matches the {{n}} token in the group's content.
            $table->unsignedSmallInteger('position')->nullable();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->boolean('vetted')->default(false);
            $table->string('content_hash', 64)->index(); // hash of normalized text + sorted options

            // Classification (verify these table names match yours)
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('my_classes')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('subject_topics')->cascadeOnDelete();
            $table->enum('difficulty_level', ['substandard', 'standard', 'advanced'])->default('standard');

            // Content
            $table->text('text');
            $table->enum('type', ['objective', 'subjective']);
            $table->unsignedTinyInteger('marks')->default(1);

            // Plain JSON array of strings. Each string is text, LaTeX ($...$),
            // or a relative media path (image/gif/audio). NULL = subjective.
            $table->json('options')->nullable();
            // JSON array of accepted answers. Objective: the exact option
            // string(s). Subjective: accepted typed answers.
            $table->json('correct_answers');

            $table->timestamps();

            $table->index(['subject_id', 'class_id', 'topic_id', 'vetted']);
            $table->index('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};