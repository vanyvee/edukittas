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

              $table->unsignedSmallInteger('created_by');
              $table->unsignedMediumInteger('subject_id');
              $table->unsignedMediumInteger('class_id');
              $table->unsignedBigInteger('topic_id');
            // Grouping. NULL group = standalone question.
            $table->foreignId('group_id')->nullable()->constrained('question_groups')->cascadeOnDelete();
            // Position inside the group. For gap groups this is the gap index
            // and matches the {{n}} token in the group's content.
            $table->unsignedSmallInteger('position')->nullable();
            $table->boolean('vetted')->default(false);
            $table->string('content_hash', 64)->index(); // hash of normalized text + sorted options

           $table->enum('difficulty_level', ['substandard', 'standard', 'advanced'])->default('standard');
            $table->foreign('created_by')->references('id')->on('admins');
              $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('class_id')->references('id')->on('my_classes');
            $table->foreign('topic_id')->references('id')->on('subject_topics');
          
          
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