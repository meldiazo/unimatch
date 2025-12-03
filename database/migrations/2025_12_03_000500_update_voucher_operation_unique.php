<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('payment_vouchers')) {
            return;
        }

        Schema::table('payment_vouchers', function (Blueprint $table) {
            try {
                $table->dropUnique('payment_vouchers_bank_account_operation_unique');
            } catch (\Throwable $e) {
                // index might not exist
            }

            if (! $this->indexExists('payment_vouchers', 'payment_vouchers_account_operation_status_unique')) {
                $table->unique(['bank_account_id', 'operation_number', 'status'], 'payment_vouchers_account_operation_status_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_vouchers')) {
            return;
        }

        Schema::table('payment_vouchers', function (Blueprint $table) {
            try {
                $table->dropUnique('payment_vouchers_account_operation_status_unique');
            } catch (\Throwable $e) {
                // ignore
            }

            if (! $this->indexExists('payment_vouchers', 'payment_vouchers_bank_account_operation_unique')) {
                $table->unique(['bank_account_id', 'operation_number'], 'payment_vouchers_bank_account_operation_unique');
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
                $name = is_object($entry) ? ($entry->name ?? null) : ($entry['name'] ?? null);
                if ($name === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            $result = $connection->select('SELECT to_regclass(?) as idx', [$index]);
            $row = $result[0] ?? null;
            $value = is_object($row) ? ($row->idx ?? null) : ($row['idx'] ?? null);
            return ! empty($value);
        }

        if ($driver === 'mysql') {
            $result = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return ! empty($result);
        }

        return false;
    }
};
