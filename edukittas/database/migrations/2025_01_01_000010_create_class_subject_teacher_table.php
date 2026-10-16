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
        Schema::create('class_subject_teachers', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->unsignedBigInteger('user_id');
             $table->uuid('uuid')->unique();
            $table->unsignedMediumInteger('class_id');
             $table->unsignedMediumInteger('subject_id');
            $table->unsignedMediumInteger('school_id');
            $table->mediumInteger('cls_fee')->nullable(); 
            $table->enum('sub_type',['s','a','b'])->default('b');       
            $table->timestamps();
            $table->primary([ 'subject_id','class_id','user_id','school_id'], 'this_primary');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('my_classes');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject_teachers');
    }
};
