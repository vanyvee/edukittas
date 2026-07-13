<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('material_id');
            $table->decimal('qty', 10, 3);   // amount of material consumed per 1 unit of product
            $table->timestamps();

            $table->unique(['product_id', 'material_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('material_id')->references('id')->on('raw_materials');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
