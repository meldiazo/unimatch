<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_vouchers', 'bank_id')) {
                $table->foreignId('bank_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'bank_id')) {
                $table->dropConstrainedForeignId('bank_id');
            }
        });
    }
};
