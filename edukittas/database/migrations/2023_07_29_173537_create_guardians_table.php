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
        
        Schema::create('guardians', function (Blueprint $table) {
             $table->engine = "InnoDB";
            $table->id();
             $table->uuid('uuid')->unique();
            $table->string('surname',20);
            $table->string('other_name',20)->nullable();
            $table->string('username',20)->unique();
            $table->enum('sex',['f','m']); 
            $table->string('passport',20)->nullable();
            $table->string('password',60)->default('12345');
            $table->string('phone',11);
            $table->string('email',20)->nullable();
            $table->timestamps();
          
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
