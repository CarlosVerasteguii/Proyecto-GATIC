<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_quantity_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->foreignId('actor_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->enum('direction', ['out', 'in']);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('qty_before');
            $table->unsignedInteger('qty_after');
            $table->text('note');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['employee_id', 'created_at']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quantity_movements');
    }
};
