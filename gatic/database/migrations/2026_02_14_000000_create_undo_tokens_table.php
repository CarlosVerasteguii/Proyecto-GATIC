<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('undo_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('movement_kind');
            $table->unsignedBigInteger('movement_id')->nullable();
            $table->uuid('batch_uuid')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['expires_at']);
            $table->index(['actor_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('undo_tokens');
    }
};
