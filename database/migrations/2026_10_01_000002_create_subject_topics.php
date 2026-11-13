<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_topics', function (Blueprint $table) {
            $table->id();
           $table->unsignedMediumInteger('subject_id');
            $table->text('title');
            $table->foreign('subject_id')->references('id')->on('subjects');
          
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_topics');
    }
};
