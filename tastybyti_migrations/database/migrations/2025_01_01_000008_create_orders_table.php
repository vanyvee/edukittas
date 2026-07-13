<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_ref', 30)->unique();          // 'TB-20250606-4521'
            $table->unsignedBigInteger('user_id')->nullable();  // cashier who created the order
            $table->enum('payment_method', ['cash', 'transfer', 'online'])->default('cash');
            $table->enum('customer_type', ['walkin', 'delivery', 'online'])->default('walkin');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending', 'cooking', 'ready', 'completed', 'cancelled'])
                  ->default('pending');
            $table->string('customer_name', 120)->nullable();   // for delivery / online orders
            $table->string('customer_phone', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
