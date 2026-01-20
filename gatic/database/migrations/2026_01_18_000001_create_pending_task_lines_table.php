<?php

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_task_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_task_id')
                ->constrained('pending_tasks')
                ->cascadeOnDelete();
            $table->enum('line_type', PendingTaskLineType::values());
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->string('serial')->nullable();
            $table->string('asset_tag')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->text('note');
            $table->enum('line_status', PendingTaskLineStatus::values())->default(PendingTaskLineStatus::Pending->value);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['pending_task_id', 'order']);
            $table->index(['line_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_task_lines');
    }
};
