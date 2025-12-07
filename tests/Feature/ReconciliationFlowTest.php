<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\ImportBatch;
use App\Models\SalesBookEntry;
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

        $line->update(['custom_identifier' => 'EXT-100']);

        $salesBatch = ImportBatch::create([
            'import_type' => 'sales_book',
            'source_name' => 'reporte.csv',
            'uploaded_by' => $user->id,
            'status' => 'completed',
        ]);

        $entry = SalesBookEntry::create([
            'import_batch_id' => $salesBatch->id,
            'row_number' => 1,
            'invoice_date' => now()->toDateString(),
            'invoice_number' => 'F-100',
            'student_name' => $student->full_name,
            'custom_id' => $student->code,
            'amount' => 500.00,
            'bank_name' => $bank->name,
            'operation_reference' => 'OP100',
            'state_label' => 'pendiente',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('ingresos.matching.confirm'), [
                'action' => 'credit',
                'bank_statement_line_id' => $line->id,
                'sales_book_entry_id' => $entry->id,
                'credit_amount' => 100.00,
            ]);

        $response->assertOk()
            ->assertJson([
                'action' => 'credit',
            ]);

        $this->assertDatabaseHas('sales_book_entries', [
            'id' => $entry->id,
            'state_label' => 'demasia',
        ]);

        $this->assertDatabaseHas('transactions', [
            'bank_statement_line_id' => $line->id,
            'sales_book_entry_id' => $entry->id,
            'status' => 'demasia',
        ]);

        $balance = StudentBalance::where('student_id', $student->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(100.00, (float) $balance->balance_amount);

        $this->assertEquals(1, Transaction::count());
    }

}
