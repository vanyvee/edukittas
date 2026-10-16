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
        Schema::create('users', function (Blueprint $table) {
              $table->engine = "InnoDB";
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('surname',20);
            $table->string('first_name',20);
            $table->string('other_name',)->nullable();
            $table->string('username',20)->unique();
            $table->enum('role',['director','ictAdmin','principal','vice principal','bursar', 'teacher','security','other'])->default('teacher');
            $table->boolean('status')->default(1);
            $table->string('salary',7)->nullable();
            $table->string('account_number',10)->nullable();
            $table->string('bank_name',10)->nullable();
            $table->string('passport',20)->nullable();
            $table->enum('sex',['m','f']);
            $table->date('dob');
            $table->unsignedSmallInteger('lg_id');
            $table->dateTime('employed_on')->default(DB::raw('NOW()'));
            $table->string('form_teacher',15)->nullable();
            $table->string('qualification',10);
            $table->string('phone',11)->nullable();
            $table->string('password',60)->default('55555');
            $table->string('email',50)->unique()->nullable();
            $table->string('recovery_phone',11)->nullable();
             $table->string('address');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->foreign('lg_id')->references('id')->on('lgas');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
