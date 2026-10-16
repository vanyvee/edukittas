<?php

use Illuminate\Database\DBAL\TimestampType;
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
        Schema::create('fees', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->unsignedBigInteger('student_id');
            $table->unsignedMediumInteger('class_id');
            $table->date('date_paid')->default(now());
            $table->mediumInteger('owing')->default(0);
            $table->mediumInteger('paid')->default(0);
            $table->enum('term',['1','2','3']);
            $table->year('session');   
            $table->primary([ 'student_id','class_id','term','date_paid'], 'this_primary');   
            $table->foreign('student_id')->references('id')->on('students');//->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('my_classes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
