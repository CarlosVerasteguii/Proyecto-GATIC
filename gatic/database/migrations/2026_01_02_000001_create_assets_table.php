<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('serial')->collation('utf8mb4_0900_ai_ci');
            $table->string('asset_tag')->nullable()->collation('utf8mb4_0900_ai_ci');
            $table->string('status')->default('Disponible')->collation('utf8mb4_0900_ai_ci');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['product_id', 'serial']);
            $table->unique('asset_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
