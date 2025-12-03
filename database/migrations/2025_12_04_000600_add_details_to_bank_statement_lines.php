<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->string('office')->nullable()->after('description');
            $table->time('transaction_time')->nullable()->after('office');
            $table->decimal('debit_amount', 14, 2)->nullable()->after('transaction_time');
            $table->decimal('credit_amount', 14, 2)->nullable()->after('debit_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn(['office', 'transaction_time', 'debit_amount', 'credit_amount']);
        });
    }
};
