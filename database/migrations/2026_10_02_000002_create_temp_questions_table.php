<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_questions', function (Blueprint $table) {
            $table->id();

           // Belongs to one CBT; flushing the CBT flushes these
            $table->foreignId('cbt_id')->constrained('cbts')->cascadeOnDelete();

            // Set when copied from the main bank (snapshot, not a live link)
            
            $table->foreignId('source_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('promoted_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('temp_question_groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->nullable();        // position within group / gap index
            $table->unsignedMediumInteger('question_number')->nullable(); // order within this CBT only

            // Classification
             $table->unsignedMediumInteger('subject_id');
              $table->unsignedMediumInteger('class_id');
              $table->unsignedBigInteger('topic_id');
            
            $table->enum('difficulty_level', ['substandard', 'standard', 'advanced'])->default('standard');

            // Content (same rules as the main bank)
            $table->text('text');
            $table->enum('type', ['objective', 'subjective']);
            $table->unsignedTinyInteger('marks')->default(1);
            $table->json('options')->nullable();
            $table->json('correct_answers');
            $table->foreign('class_id')->references('id')->on('my_classes');
            $table->foreign('topic_id')->references('id')->on('subject_topics');
             $table->foreign('subject_id')->references('id')->on('subjects');
          
            $table->timestamps();

            $table->index(['cbt_id', 'question_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_questions');
    }
};