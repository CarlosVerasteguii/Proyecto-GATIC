<?php

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_tasks', function (Blueprint $table) {
            $table->id();
            $table->enum('type', PendingTaskType::values());
            $table->text('description')->nullable();
            $table->enum('status', PendingTaskStatus::values())->default(PendingTaskStatus::Draft->value);
            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('locked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'creator_user_id', 'created_at']);
            $table->index(['locked_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_tasks');
    }
};
