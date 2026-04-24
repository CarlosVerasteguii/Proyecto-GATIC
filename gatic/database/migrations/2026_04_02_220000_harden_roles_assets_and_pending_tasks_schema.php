<?php

use App\Enums\UserRole;
use App\Models\Asset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USERS_ROLE_CHECK = 'users_role_allowed_check';

    private const ASSETS_STATUS_CHECK = 'assets_status_allowed_check';

    private const PENDING_TASKS_CREATED_AT_INDEX = 'pending_tasks_created_at_index';

    private const PENDING_TASKS_TYPE_CREATED_AT_INDEX = 'pending_tasks_type_created_at_index';

    public function up(): void
    {
        Schema::table('pending_tasks', function (Blueprint $table) {
            $table->index('created_at', self::PENDING_TASKS_CREATED_AT_INDEX);
            $table->index(['type', 'created_at'], self::PENDING_TASKS_TYPE_CREATED_AT_INDEX);
        });

        $this->addMySqlCheckConstraint(
            table: 'users',
            name: self::USERS_ROLE_CHECK,
            column: 'role',
            allowedValues: UserRole::values(),
        );

        $this->addMySqlCheckConstraint(
            table: 'assets',
            name: self::ASSETS_STATUS_CHECK,
            column: 'status',
            allowedValues: Asset::STATUSES,
        );
    }

    public function down(): void
    {
        $this->dropMySqlCheckConstraint('assets', self::ASSETS_STATUS_CHECK);
        $this->dropMySqlCheckConstraint('users', self::USERS_ROLE_CHECK);

        Schema::table('pending_tasks', function (Blueprint $table) {
            $table->dropIndex(self::PENDING_TASKS_CREATED_AT_INDEX);
            $table->dropIndex(self::PENDING_TASKS_TYPE_CREATED_AT_INDEX);
        });
    }

    /**
     * @param  list<string>  $allowedValues
     */
    private function addMySqlCheckConstraint(
        string $table,
        string $name,
        string $column,
        array $allowedValues,
    ): void {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $quotedValues = implode(', ', array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $allowedValues,
        ));

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (BINARY `%s` IN (%s))',
            $table,
            $name,
            $column,
            $quotedValues,
        ));
    }

    private function dropMySqlCheckConstraint(string $table, string $name): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP CHECK `%s`',
            $table,
            $name,
        ));
    }
};
