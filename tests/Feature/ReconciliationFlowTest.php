<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\ImportBatch;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\StudentBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_user_can_register_demasia_and_credit_balance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ENCARGADO_INGRESOS]);
        $student = Student::factory()->create();
        $bank = Bank::factory()->create(['short_code' => 'BNB10']);
        $account = BankAccount::factory()->create([
            'bank_id' => $bank->id,
            'account_number' => '96300001',
        ]);

        $batch = ImportBatch::create([
            'import_type' => 'extractos',
            'source_name' => 'test.csv',
            'uploaded_by' => $user->id,
            'status' => 'completed',
        ]);

        $statement = BankStatement::unguarded(fn () => BankStatement::create([
            'bank_id' => $bank->id,
            'bank_account_id' => $account->id,
            'import_batch_id' => $batch->id,
            'statement_date' => now()->toDateString(),
            'currency' => 'BOB',
            'status' => 'completed',
        ]));

        $line = BankStatementLine::create([
            'bank_statement_id' => $statement->id,
            'line_number' => 1,
            'operation_number' => 'OP100',
            'description' => 'Pago matrícula',
            'amount' => 600.00,
            'currency' => 'BOB',
        ]);

        $voucher = PaymentVoucher::create([
            'student_id' => $student->id,
            'bank_id' => $bank->id,
            'bank_account_id' => $account->id,
            'operation_number' => 'OP100',
            'payment_type' => 'Transferencia',
            'amount' => 500.00,
            'currency' => 'BOB',
            'paid_at' => now()->toDateString(),
            'received_at' => now()->toDateString(),
            'status' => 'recibido',
            'billing_status' => 'pendiente',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('ingresos.matching.confirm'), [
                'action' => 'credit',
                'bank_statement_line_id' => $line->id,
                'payment_voucher_id' => $voucher->id,
                'credit_amount' => 100.00,
            ]);

        $response->assertOk()
            ->assertJson([
                'action' => 'credit',
            ]);

        $this->assertDatabaseHas('payment_vouchers', [
            'id' => $voucher->id,
            'status' => 'demasia',
        ]);

        $this->assertDatabaseHas('transactions', [
            'bank_statement_line_id' => $line->id,
            'payment_voucher_id' => $voucher->id,
            'status' => 'demasia',
        ]);

        $balance = StudentBalance::where('student_id', $student->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(100.00, (float) $balance->balance_amount);

        $this->assertEquals(1, Transaction::count());
    }

    public function test_cajero_only_marks_facturado_for_valid_vouchers(): void
    {
        $cajero = User::factory()->create(['role' => User::ROLE_CAJERO]);
        $student = Student::factory()->create();

        $voucher = PaymentVoucher::create([
            'student_id' => $student->id,
            'operation_number' => 'OP200',
            'payment_type' => 'Transferencia',
            'amount' => 400.00,
            'currency' => 'BOB',
            'paid_at' => now()->toDateString(),
            'received_at' => now()->toDateString(),
            'status' => 'recibido',
            'billing_status' => 'pendiente',
        ]);

        $this->actingAs($cajero)
            ->from('/cajero')
            ->patch(route('cajero.billing.update', $voucher), [
                'billing_status' => 'facturado',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors('billing_status');

        $this->assertEquals('pendiente', $voucher->fresh()->billing_status);

        $conciliated = PaymentVoucher::create([
            'student_id' => $student->id,
            'operation_number' => 'OP201',
            'payment_type' => 'Transferencia',
            'amount' => 250.00,
            'currency' => 'BOB',
            'paid_at' => now()->toDateString(),
            'received_at' => now()->toDateString(),
            'status' => 'conciliado',
            'billing_status' => 'pendiente',
        ]);

        $this->actingAs($cajero)
            ->from('/cajero')
            ->patch(route('cajero.billing.update', $conciliated), [
                'billing_status' => 'facturado',
            ])
            ->assertStatus(302)
            ->assertSessionHas('status', 'Estado de facturación actualizado.');

        $conciliated->refresh();
        $this->assertEquals('facturado', $conciliated->billing_status);
        $this->assertNotNull($conciliated->billed_at);
        $this->assertEquals($cajero->id, $conciliated->billed_by);

        $demasia = PaymentVoucher::create([
            'student_id' => $student->id,
            'operation_number' => 'OP202',
            'payment_type' => 'Transferencia',
            'amount' => 250.00,
            'currency' => 'BOB',
            'paid_at' => now()->toDateString(),
            'received_at' => now()->toDateString(),
            'status' => 'demasia',
            'billing_status' => 'pendiente',
        ]);

        $this->actingAs($cajero)
            ->from('/cajero')
            ->patch(route('cajero.billing.update', $demasia), [
                'billing_status' => 'facturado',
            ])
            ->assertStatus(302)
            ->assertSessionHas('status', 'Estado de facturación actualizado.');

        $this->assertEquals('facturado', $demasia->fresh()->billing_status);
    }
}
