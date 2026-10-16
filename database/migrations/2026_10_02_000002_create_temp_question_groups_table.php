<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_question_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cbt_id')->constrained('cbts')->cascadeOnDelete();

            // Set when copied from the main bank (snapshot, not a live link)
            $table->foreignId('source_group_id')->nullable()->constrained('question_groups')->nullOnDelete();
            $table->foreignId('promoted_group_id')->nullable()->constrained('question_groups')->nullOnDelete();
            $table->string('title');
            $table->text('introduction')->nullable();
            $table->longText('content');
            $table->string('type', 20);                 // gap | passage | diagram | audio

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_question_groups');
    }
};