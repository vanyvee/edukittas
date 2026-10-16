<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbts', function (Blueprint $table) {
            $table->id();

            // Tenant: every CBT belongs to a school.
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Who set it up: an Enrollment (student) or a User (teacher/staff).
            $table->morphs('host');

            $table->string('title');
            $table->enum('type', ['exam', 'test', 'practice', 'quiz']);

            $table->unsignedSmallInteger('duration_minutes')->nullable(); // personal timer
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();                    // hard stop for everyone

            // A scheduled command deletes expired CBTs; cascades remove their temp questions.
            $table->dateTime('expires_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbts');
    }
};
