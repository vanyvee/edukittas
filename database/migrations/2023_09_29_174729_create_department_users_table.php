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
        Schema::create('department_users', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->unsignedBigInteger('user_id');
            $table->unsignedMediumInteger('department_id');
            $table->unsignedMediumInteger('school_id');   
            $table->timestamps();
            $table->unique(['user_id','department_id',    'school_id',]);
            $table->primary(['user_id','school_id','department_id'], 'this_primary');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_users');
    }
};
