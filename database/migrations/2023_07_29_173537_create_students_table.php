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
            
            $table->string('pix',20)->nullable();
           
           
            $table->string('passport',20)->nullable();
            $table->unsignedSmallInteger('lg_id');
            $table->string('nin',11)->nullable();
            $table->string('password',60)->default('12345');
            $table->string('phone',11);
            $table->string('email',30)->nullable();
          
            $table->unsignedBigInteger('guardian_id')->nullable();

            $table->timestamps();
           
            $table->foreign('lg_id')->references('id')->on('lgas');
          
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
