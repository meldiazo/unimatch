<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->string('custom_identifier')->nullable()->after('credit_amount');
            $table->date('billing_reference_date')->nullable()->after('custom_identifier');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn(['custom_identifier', 'billing_reference_date']);
        });
    }
};
