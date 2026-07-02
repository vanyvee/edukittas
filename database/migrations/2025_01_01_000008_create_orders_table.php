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
            
            $table->unsignedBigInteger('user_id')->nullable();  // cashier who created the order
           $table->unsignedBigInteger('customer_id')->nullable();
            $table->enum('payment_method', ['cash', 'transfer'])->default('transfer');
            $table->enum('customer_type', ['walkin', 'online'])->default('walkin');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending','cooking' ,'ready'])->default('pending');
            $table->string('customer_name', 80)->nullable();   // for delivery / online orders
            $table->string('customer_phone', 11)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->string('guest_token', 60)->nullable();
            $table->index('status');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
