<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = Schema::getConnection();
        $isSqlite = $connection->getDriverName() === 'sqlite';

        Schema::table('bank_statements', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite) {
                return;
            }

            if (Schema::hasColumn('bank_statements', 'bank_id')) {
                $table->dropForeign(['bank_id']);
                $table->dropColumn('bank_id');
            }
        });

        $hadBankColumn = Schema::hasColumn('payment_vouchers', 'bank_id');
        $hadBankOperationIndex = $this->indexExists('payment_vouchers', 'payment_vouchers_operation_number_bank_id_index');
        $hadBankOperationUnique = $this->indexExists('payment_vouchers', 'payment_vouchers_bank_id_operation_number_unique');
        $hasAccountOperationUnique = $this->indexExists('payment_vouchers', 'payment_vouchers_bank_account_operation_unique');

        Schema::table('payment_vouchers', function (Blueprint $table) use (
            $hadBankColumn,
            $hadBankOperationIndex,
            $hadBankOperationUnique,
            $hasAccountOperationUnique,
            $isSqlite
        ) {
            if ($hadBankColumn) {
                if ($hadBankOperationIndex) {
                    $table->dropIndex('payment_vouchers_operation_number_bank_id_index');
                }

                if ($hadBankOperationUnique) {
                    $table->dropUnique('payment_vouchers_bank_id_operation_number_unique');
                }

                if (! $isSqlite) {
                    $table->dropColumn('bank_id');
                }
            }

            if (! $hasAccountOperationUnique) {
                $table->unique(['bank_account_id', 'operation_number'], 'payment_vouchers_bank_account_operation_unique');
            }
        });
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $isSqlite = $connection->getDriverName() === 'sqlite';

        Schema::table('bank_statements', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite) {
                return;
            }

            if (! Schema::hasColumn('bank_statements', 'bank_id')) {
                $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            }
        });

        $hasAccountOperationUnique = $this->indexExists('payment_vouchers', 'payment_vouchers_bank_account_operation_unique');
        $hasBankColumn = Schema::hasColumn('payment_vouchers', 'bank_id');
        $hasBankOperationUnique = $this->indexExists('payment_vouchers', 'payment_vouchers_bank_id_operation_number_unique');

        Schema::table('payment_vouchers', function (Blueprint $table) use (
            $hasAccountOperationUnique,
            $hasBankColumn,
            $hasBankOperationUnique,
            $isSqlite
        ) {
            if ($hasAccountOperationUnique) {
                $table->dropUnique('payment_vouchers_bank_account_operation_unique');
            }

            if (! $hasBankColumn && ! $isSqlite) {
                $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! $hasBankOperationUnique && ! $isSqlite) {
                $table->unique(['bank_id', 'operation_number'], 'payment_vouchers_bank_id_operation_number_unique');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('".$table."')");
            foreach ($indexes as $entry) {
                $name = is_object($entry) ? $entry->name : ($entry['name'] ?? null);
                if ($name === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $result = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return ! empty($result);
        }

        if ($driver === 'pgsql') {
            $result = $connection->select("SELECT to_regclass(?) as idx", [$index]);
            $row = $result[0] ?? null;
            $value = is_object($row) ? ($row->idx ?? null) : ($row['idx'] ?? null);
            return ! empty($value);
        }

        return false;
    }
};
