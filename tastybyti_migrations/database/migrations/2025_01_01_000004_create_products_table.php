<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('category_id');
            $table->string('name', 160);
            $table->decimal('price', 10, 2)->nullable();  // NULL = cashier sets price at order time
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_price_open')->default(false); // true = DELIVERY, PAINT SIP, etc.
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
