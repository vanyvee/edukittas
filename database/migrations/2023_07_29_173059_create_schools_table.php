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
        Schema::create('schools', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->mediumIncrements('id'); 
            $table->string('name',100);
            $table->unsignedSmallInteger('lg_id');
            $table->date('dob');
            $table->string('phone',11);
            $table->string('email',50)->unique();
            $table->string('address',30);
            $table->string('pix',50)->unique()->nullable();
            $table->boolean('status')->default(0);
            $table->year('current_session');
            $table->enum('current_term',['1','2','3']);
            $table->boolean('type')->default(0);
            $table->tinyInteger('promotion_grade')->default(45);
            $table->string('logo',30)->nullable();
            $table->enum('result_template',['a','b','c'])->default('a');
            $table->enum('theme',['a','b','c'])->default('a');
             // Short link code for edukit.test/greenfield (LandingController reads this column).
            $table->string('url_code', 19)->nullable()->unique();

            // Only featured, active schools appear in the guest picker and hero.
            $table->boolean('featured')->default(false)->index();

            // What a visitor sees on the guest page. The cover image is the existing `pix` column.
            $table->string('motto', 120)->nullable();
            $table->text('about')->nullable();
            $table->string('website', 100)->nullable();
            $table->json('photos')->nullable();   // list of image paths
            $table->timestamps();
            $table->foreign('lg_id')->references('id')->on('lgas');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
