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
        Schema::create('admins', function (Blueprint $table) {
            $table->engine = "InnoDB";
            
          $table->smallIncrements('id');
              $table->string('username',20)->unique();
            $table->string('email',30)->unique();
            $table->string('password',60);
            $table->string('phone',11);
            $table->string('recovery_phone',11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
