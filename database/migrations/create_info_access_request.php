<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One table for both paid-lookup types (trace = "where did this
     * student go", remarks = "what did prior schools say"), distinguished
     * by `type` rather than two near-identical tables. `payload` holds the
     * revealed answer once paid — a snapshot, not a live join, so what was
     * paid for can't silently change later.
     */
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('requesting_school_id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['trace', 'remarks']);
            $table->enum('status', ['pending', 'paid', 'fulfilled'])->default('pending');
            $table->mediumInteger('fee_amount')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('requesting_school_id')->references('id')->on('my_schools');
            $table->index(['student_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};