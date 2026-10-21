<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->unsignedMediumInteger('school_id');
            $table->unsignedMediumInteger('subject_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('sub_type',['s','a','b'])->default('b');
            $table->unsignedTinyInteger('other_test')->nullable();
            $table->unsignedMediumInteger('class_id');
            $table->tinyInteger('first_test')->nullable();
            $table->tinyInteger('second_test')->nullable();
            $table->tinyInteger('exam')->nullable();
            $table->boolean('compiled')->default(0);
            $table->enum('term',['1','2','3']);
            $table->year('session');
            $table->timestamps();
            $table->primary([ 'school_id','subject_id','class_id','student_id',
                            'term','session'], 'this_primary');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('class_id')->references('id')->on('my_classes');
            $table->foreign('student_id')->references('id')->on('students');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
