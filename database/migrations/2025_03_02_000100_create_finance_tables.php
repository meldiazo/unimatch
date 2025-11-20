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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_code')->unique();
            $table->string('status')->default('active');
            $table->jsonb('format_config')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('account_number');
            $table->string('currency', 8)->default('BOB');
            $table->boolean('active')->default(true);
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->unique(['bank_id', 'account_number']);
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('import_type'); // extractos, facturacion, vouchers
            $table->string('source_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->string('status')->default('pending');
            $table->jsonb('summary_data')->nullable();
            $table->jsonb('errors')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->date('statement_date')->nullable();
            $table->string('currency', 8)->default('BOB');
            $table->decimal('opening_balance', 14, 2)->nullable();
            $table->decimal('closing_balance', 14, 2)->nullable();
            $table->string('status')->default('pending');
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number')->nullable();
            $table->string('operation_number')->nullable()->index();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->date('operation_date')->nullable();
            $table->date('value_date')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('currency', 8)->default('BOB');
            $table->decimal('running_balance', 14, 2)->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('full_name');
            $table->string('program')->nullable();
            $table->string('email')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('student_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 8)->default('BOB');
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'currency']);
        });

        Schema::create('invoice_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('nit_ci')->nullable();
            $table->string('razon_social')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('BOB');
            $table->date('issued_at')->nullable();
            $table->string('status')->default('emitida');
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cashbox_number')->nullable();
            $table->string('operation_number')->nullable()->index();
            $table->string('payment_type')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('BOB');
            $table->date('paid_at')->nullable();
            $table->date('received_at')->nullable();
            $table->string('account_reference')->nullable();
            $table->string('status')->default('recibido');
            $table->string('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['operation_number', 'bank_id']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_voucher_id')->nullable()->constrained('payment_vouchers')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->decimal('difference_amount', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payment_vouchers');
        Schema::dropIfExists('voucher_batches');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_batches');
        Schema::dropIfExists('student_balances');
        Schema::dropIfExists('students');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
    }
};
