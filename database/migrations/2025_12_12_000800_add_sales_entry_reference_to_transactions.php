<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'sales_book_entry_id')) {
                $table->foreignId('sales_book_entry_id')
                    ->nullable()
                    ->after('payment_voucher_id')
                    ->constrained('sales_book_entries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'sales_book_entry_id')) {
                $table->dropForeign(['sales_book_entry_id']);
                $table->dropColumn('sales_book_entry_id');
            }
        });
    }
};
