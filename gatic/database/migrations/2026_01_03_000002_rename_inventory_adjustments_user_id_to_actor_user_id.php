<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_adjustments')) {
            return;
        }

        if (Schema::hasColumn('inventory_adjustments', 'actor_user_id')) {
            return;
        }

        if (! Schema::hasColumn('inventory_adjustments', 'user_id')) {
            return;
        }

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE inventory_adjustments CHANGE user_id actor_user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->foreign('actor_user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_adjustments')) {
            return;
        }

        if (Schema::hasColumn('inventory_adjustments', 'user_id')) {
            return;
        }

        if (! Schema::hasColumn('inventory_adjustments', 'actor_user_id')) {
            return;
        }

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropForeign(['actor_user_id']);
        });

        DB::statement('ALTER TABLE inventory_adjustments CHANGE actor_user_id user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
