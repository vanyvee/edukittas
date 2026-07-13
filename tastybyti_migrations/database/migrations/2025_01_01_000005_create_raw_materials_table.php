<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 120);
            $table->string('unit', 20);             // 'pcs', 'kg', 'ml', 'litres', etc.
            $table->decimal('stock', 10, 3)->default(0);
            $table->decimal('threshold', 10, 3)->default(0);  // low-stock warning level
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
