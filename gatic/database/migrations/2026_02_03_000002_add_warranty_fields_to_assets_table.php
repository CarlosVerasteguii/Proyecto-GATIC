<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable()->index();
            $table->foreignId('warranty_supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->text('warranty_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['warranty_supplier_id']);
            $table->dropIndex(['warranty_end_date']);
            $table->dropColumn([
                'warranty_start_date',
                'warranty_end_date',
                'warranty_supplier_id',
                'warranty_notes',
            ]);
        });
    }
};
