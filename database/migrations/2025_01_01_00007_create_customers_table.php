<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
  use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
   

          

$table->uuid('uuid')
      ->unique()
      ->default(DB::raw('(UUID())'));
            $table->string('username');
            $table->string('surname');
            $table->string('first_name');
            $table->boolean('status')->default(0);
            $table->string('password')->nullable();
            $table->string('phone');
            $table->string('address');
            $table->tinyInteger('day')->nullable();
            $table->tinyInteger('month')->nullable();
            $table->smallInteger('year')->nullable();
            $table->timestamps();
        });

    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        
    }
};
