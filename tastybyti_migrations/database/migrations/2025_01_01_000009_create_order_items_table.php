<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('product_id')->nullable();  // nullable: product may be deleted later
            $table->string('name', 160);        // snapshot — frozen at time of sale
            $table->decimal('price', 10, 2);    // snapshot — frozen at time of sale
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('subtotal', 10, 2); // price × qty, stored for query performance
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
