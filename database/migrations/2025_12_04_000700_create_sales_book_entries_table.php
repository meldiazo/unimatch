<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('legacy_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('nit_ci')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('student_name')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('account_label')->nullable();
            $table->string('state_label')->nullable();
            $table->string('custom_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('recorded_date')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_book_entries');
    }
};
