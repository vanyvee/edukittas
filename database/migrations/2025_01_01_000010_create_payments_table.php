<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
              $table->engine = "InnoDB";
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('method', ['cash', 'transfer', 'online']);
            $table->decimal('amount', 10, 2);
            $table->string('reference', 100)->nullable();  // Opay ref, bank ref, etc.
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('confirmed');
            $table->unsignedBigInteger('confirmed_by')->nullable();  // user_id who confirmed
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
