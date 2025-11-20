<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('transactions', 'invoice_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_id');
            });
        }

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_batches');

        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->string('billing_status')->default('pendiente')->after('status');
            $table->timestamp('billed_at')->nullable()->after('billing_status');
            $table->foreignId('billed_by')->nullable()->after('billed_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropForeign(['billed_by']);
            $table->dropColumn(['billing_status', 'billed_at', 'billed_by']);
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

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
        });
    }
};
