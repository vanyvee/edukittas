<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admission enquiries sent from a school's public page. They belong to the school, so its staff
     * can read them later.
     */
    public function up(): void
    {
        Schema::create('school_enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('school_id');
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'read', 'closed'])->default('new');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_enquiries');
    }
};
