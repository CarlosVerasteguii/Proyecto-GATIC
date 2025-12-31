<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();

            $table->string('error_id', 64)->unique();
            $table->string('environment', 32);

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role', 32)->nullable();

            $table->string('method', 16)->nullable();
            $table->text('url')->nullable();
            $table->string('route', 255)->nullable();

            $table->string('exception_class', 255);
            $table->text('exception_message')->nullable();
            $table->longText('stack_trace')->nullable();

            $table->json('context')->nullable();

            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};
