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
        Schema::create('my_classes', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->mediumIncrements('id'); 
            $table->string('name',15);
            $table->string('level',15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_classes');
    }
};
