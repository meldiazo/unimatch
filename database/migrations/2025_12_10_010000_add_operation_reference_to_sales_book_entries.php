<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_book_entries', function (Blueprint $table) {
            $table->string('operation_reference')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('sales_book_entries', function (Blueprint $table) {
            $table->dropColumn('operation_reference');
        });
    }
};
