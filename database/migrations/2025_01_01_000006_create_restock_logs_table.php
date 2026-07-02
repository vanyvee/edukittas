<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('material_id');
            $table->unsignedBigInteger('user_id')->nullable();  // who restocked (null = system/import)
            $table->decimal('qty_added', 10, 3);
            $table->decimal('stock_before', 10, 3);
            $table->decimal('stock_after', 10, 3);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('material_id')->references('id')->on('raw_materials');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_logs');
    }
};
