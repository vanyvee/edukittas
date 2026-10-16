<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::create('students', function (Blueprint $table) {
             $table->engine = "InnoDB";
            $table->id();
             $table->uuid('uuid')->unique();
            $table->string('surname',20);
            $table->string('first_name',20);
            $table->string('other_name',20)->nullable();
            $table->string('username',20)->unique();
            $table->date('dob');
            $table->enum('sex',['f','m']); 
            $table->unsignedMediumInteger('class_id');
            $table->enum('status',['0','1','2'])->default(1);
            $table->date('addmission_date')->default(date('y-m-d'));
            $table->date('graduation_date')->nullable();
            $table->enum('sub_type',['s','a','b'])->default('b');
            $table->string('pix',20)->nullable();
            $table->smallInteger('post')->default('0');
           
            $table->string('passport',20)->nullable();
            $table->unsignedSmallInteger('lg_id');
            $table->string('nin',13)->nullable();
            $table->string('password',60)->default('12345');
            $table->string('phone',11);
            $table->string('email',20)->nullable();
            $table->enum('arm',['0','A','B','C','D','E','F','G','H','I','J'])->default('0');
            $table->unsignedMediumInteger('school_id');
            $table->unsignedBigInteger('guardian_id');
            $table->mediumInteger('Fees');
            $table->timestamps();
            $table->foreign('class_id')->references('id')->on('my_classes');//->cascadeOnDelete();
            $table->foreign('lg_id')->references('id')->on('lgas');
            $table->foreign('school_id')->references('id')->on('schools');//->cascadeOnDelete();
            $table->foreign('guardian_id')->references('id')->on('guardians');//->cascadeOnDelete();
                                  
            
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
