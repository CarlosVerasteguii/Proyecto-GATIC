<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_useful_life_months')
                ->nullable()
                ->after('requires_asset_tag');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedSmallInteger('useful_life_months')
                ->nullable()
                ->after('acquisition_currency');
            $table->date('expected_replacement_date')
                ->nullable()
                ->after('useful_life_months')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['expected_replacement_date']);
            $table->dropColumn([
                'useful_life_months',
                'expected_replacement_date',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('default_useful_life_months');
        });
    }
};
