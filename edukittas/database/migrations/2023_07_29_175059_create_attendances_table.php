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
        Schema::create('attendances', function (Blueprint $table) {
           $table->engine = "InnoDB";
            $table->unsignedMediumInteger('school_id');
            $table->string('sid_date_cid',255);
            $table->year('session');
            $table->enum('term',['1','2','3']);
            $table->date('date');
            $table->primary([ 'date','session','term','school_id'], 'this_primary');
            $table->foreign('school_id')->references('id')->on('schools');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
