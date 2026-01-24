<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 100);
            $table->unsignedBigInteger('attachable_id');
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('original_name');
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            // Index for listing attachments by entity (AC3: pagination + performance)
            $table->index(['attachable_type', 'attachable_id', 'created_at']);

            // Index for filtering by uploader
            $table->index(['uploaded_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
