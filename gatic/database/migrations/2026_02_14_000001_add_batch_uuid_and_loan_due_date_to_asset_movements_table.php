<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->after('actor_user_id');
            $table->date('loan_due_date')->nullable()->after('type');

            $table->index(['batch_uuid']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->dropIndex(['batch_uuid']);
            $table->dropColumn(['batch_uuid', 'loan_due_date']);
        });
    }
};
