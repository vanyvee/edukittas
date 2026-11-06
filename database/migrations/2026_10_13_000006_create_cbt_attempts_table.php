<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_attempts', function (Blueprint $table) {
            $table->id();

            // Nullable + nullOnDelete: when an expired CBT is flushed, the attempt
            // (and its score) stays behind; only the questions disappear.
            $table->foreignId('cbt_id')->nullable()->constrained('cbts')->nullOnDelete();

            // Who took it: an Enrollment (student) or a User (teacher/staff).
            $table->morphs('taker');


$table->string('cbt_title')->nullable()->after('cbt_id');
        $table->unsignedMediumInteger('subject_id')->nullable(); // no FK on purpose
        $table->dateTime('pushed_at')->nullable();
        $table->index(['subject_id', 'submitted_at']);
        $table->dateTime('started_at');
            $table->dateTime('ends_at')->nullable();       // computed once at start
            $table->dateTime('submitted_at')->nullable();

            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('total')->nullable();

            $table->timestamps();

            // No retakes: one attempt per taker per CBT.
            $table->unique(['cbt_id', 'taker_type', 'taker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_attempts');
    }
};
