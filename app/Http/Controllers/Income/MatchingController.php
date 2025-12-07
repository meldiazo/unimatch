<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use App\Models\SalesBookEntry;
use App\Models\StudentBalance;
use App\Models\ReconciliationSetting as ReconciliationSettingModel;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ReconciliationSettings;
use App\Support\StudentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MatchingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]), 403);

        $validated = $request->validate([
            'action' => ['required', 'in:confirm,reject,credit'],
            'bank_statement_line_id' => ['required', 'exists:bank_statement_lines,id'],
            'sales_book_entry_id' => ['required', 'exists:sales_book_entries,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'credit_amount' => ['nullable', 'numeric', 'min:0.01'],
            'line_updates' => ['nullable', 'array'],
            'line_updates.custom_identifier' => ['nullable', 'string', 'max:100'],
            'line_updates.billing_reference_date' => ['nullable', 'date'],
            'entry_updates' => ['nullable', 'array'],
            'entry_updates.custom_id' => ['nullable', 'string', 'max:100'],
            'entry_updates.bank_name' => ['nullable', 'string', 'max:255'],
            'entry_updates.recorded_date' => ['nullable', 'date'],
            'entry_updates.operation_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = app(ReconciliationSettings::class)->current();

        $line = BankStatementLine::with('transaction')->findOrFail($validated['bank_statement_line_id']);
        $entry = SalesBookEntry::with('transaction')->findOrFail($validated['sales_book_entry_id']);

        $this->applyLineUpdates($line, $validated['line_updates'] ?? []);
        $this->applyEntryUpdates($entry, $validated['entry_updates'] ?? []);

        $action = $validated['action'];

        return match ($action) {
            'reject' => $this->rejectMatch($line, $entry, $validated['reason'] ?? null, $request->user()),
            'credit' => $this->creditMatch($line, $entry, $validated, $request->user(), $settings),
            default => $this->confirmMatch($line, $entry, $request->user()),
        };
    }

    private function confirmMatch(BankStatementLine $line, SalesBookEntry $entry, User $user): JsonResponse
    {
        if ($response = $this->ensurePending($line, $entry)) {
            return $response;
        }

        if ($response = $this->ensureManualIdentifiers($line, $entry)) {
            return $response;
        }

        $student = StudentResolver::resolveFromEntry($entry);
        $payload = $this->finalizeMatch($line, $entry, $user, 'conciliado', null, $student?->id);

        return response()->json(array_merge($payload, [
            'action' => 'confirm',
            'message' => 'Coincidencia confirmada.',
        ]));
    }

    private function creditMatch(BankStatementLine $line, SalesBookEntry $entry, array $validated, User $user, ReconciliationSettingModel $settings): JsonResponse
    {
        if ($response = $this->ensurePending($line, $entry)) {
            return $response;
        }

        if ($response = $this->ensureManualIdentifiers($line, $entry)) {
            return $response;
        }

        $difference = $this->resolveLineAmount($line) - (float) $entry->amount;
        $creditAmount = (float) ($validated['credit_amount'] ?? $difference);

        if ($creditAmount <= 0) {
            return response()->json([
                'message' => 'No hay monto adicional para registrar como crédito.',
            ], 422);
        }

        if ($creditAmount > $difference) {
            $creditAmount = $difference;
        }

        $creditLimit = (float) $settings->credit_max_amount;
        if ($creditLimit > 0 && $creditAmount > $creditLimit) {
            return response()->json([
                'message' => 'El monto excede el límite permitido para créditos.',
            ], 422);
        }

        $student = StudentResolver::resolveFromEntry($entry, true);
        if (! $student) {
            return response()->json([
                'message' => 'No se pudo identificar al estudiante para acreditar la demasía.',
            ], 422);
        }

        $balance = StudentBalance::firstOrCreate(
            [
                'student_id' => $student->id,
                'currency' => 'BOB',
            ],
            ['balance_amount' => 0]
        )->loadMissing('student');

        $balance->increment('balance_amount', $creditAmount);
        $payload = $this->finalizeMatch($line, $entry, $user, 'demasia', 'Pago en demasía', $student->id);

        return response()->json(array_merge($payload, [
            'action' => 'credit',
            'credit_amount' => round($creditAmount, 2),
            'overpayment' => [
                'id' => 'sb-'.$balance->id,
                'db_id' => $balance->id,
                'student_name' => $balance->student?->full_name
                    ?? $entry->student_name
                    ?? 'Sin estudiante',
                'student_code' => $balance->student?->code ?? $entry->custom_id,
                'balance' => (float) $balance->balance_amount,
                'credited_at' => now()->toDateTimeString(),
            ],
            'message' => 'Pago registrado como demasía y saldo acreditado al estudiante.',
        ]));
    }

    private function rejectMatch(BankStatementLine $line, SalesBookEntry $entry, ?string $reason, User $user): JsonResponse
    {
        if ($response = $this->ensurePending($line, $entry)) {
            return $response;
        }

        if ($response = $this->ensureManualIdentifiers($line, $entry)) {
            return $response;
        }

        $reasonText = $reason ?: 'Rechazado en conciliación';
        $payload = $this->finalizeMatch($line, $entry, $user, 'rechazado', $reasonText);
        $payload['report_entry']['reason'] = $reasonText;

        return response()->json(array_merge($payload, [
            'action' => 'reject',
            'message' => 'Registro marcado como rechazado.',
        ]));
    }

    private function ensurePending(BankStatementLine $line, SalesBookEntry $entry): ?JsonResponse
    {
        if ($line->transaction) {
            return response()->json([
                'message' => 'La transacción del extracto ya fue conciliada.',
            ], 422);
        }

        if ($entry->transaction) {
            return response()->json([
                'message' => 'El registro del reporte diario ya fue conciliado.',
            ], 422);
        }

        return null;
    }

    private function ensureManualIdentifiers(BankStatementLine $line, SalesBookEntry $entry): ?JsonResponse
    {
        if (! filled($line->custom_identifier) || ! filled($entry->custom_id)) {
            return response()->json([
                'message' => 'Debes asignar los ID manuales en el extracto y en el reporte diario antes de conciliar.',
            ], 422);
        }

        return null;
    }

    private function finalizeMatch(
        BankStatementLine $line,
        SalesBookEntry $entry,
        User $user,
        string $status = 'conciliado',
        ?string $reason = null,
        ?int $studentId = null
    ): array
    {
        $line->loadMissing('statement.account.bank');
        $lineAmount = $this->resolveLineAmount($line);
        $difference = $lineAmount - (float) $entry->amount;
        if ($studentId === null) {
            $studentId = optional(StudentResolver::resolveFromEntry($entry))->id;
        }

        $transaction = Transaction::create([
            'bank_statement_line_id' => $line->id,
            'sales_book_entry_id' => $entry->id,
            'student_id' => $studentId,
            'status' => $status,
            'notes' => null,
            'matched_by' => $user->id,
            'matched_at' => now(),
            'difference_amount' => $difference,
        ]);

        $entry->update([
            'state_label' => $status,
        ]);

        $bank = optional($line->statement?->account?->bank);
        $accountLabel = $entry->account_label ?? $line->statement?->account?->number ?? '—';

        return [
            'transaction' => [
                'id' => 'tx-'.$line->id,
                'db_id' => $line->id,
                'update_url' => route('ingresos.statements.update', $line),
                'student' => $entry->student_name ?? 'Sin estudiante',
                'enrollment' => $entry->custom_id ?? '—',
                'status' => $status,
                'billing_status' => $entry->state_label ?? 'pendiente',
                'bank_name' => $bank?->name,
            ],
            'report_entry' => [
                'id' => 'sr-'.$entry->id,
                'db_id' => $entry->id,
                'update_url' => route('ingresos.sales-report.update', $entry),
            ],
            'reconciliation' => [
                'id' => $transaction->id,
                'transactionId' => $line->id,
                'entryId' => $entry->id,
                'bankId' => $bank->id,
                'bank_name' => $entry->bank_name ?? $bank?->name,
                'student' => $entry->student_name ?? $student?->full_name ?? 'Sin estudiante',
                'amount' => (float) $lineAmount,
                'date' => optional($transaction->matched_at ?? now())->toDateString(),
                'report_date' => optional($entry->invoice_date)?->toDateString(),
                'reconciliation_date' => optional($transaction->matched_at)?->toDateTimeString(),
                'status' => $status,
                'billing_status' => $entry->state_label ?? 'pendiente',
                'operation_reference' => $entry->operation_reference ?? $line->operation_number,
                'invoice_number' => $entry->invoice_number,
                'nit_ci' => $entry->nit_ci,
                'razon_social' => $entry->razon_social,
                'payment_type' => $entry->payment_type,
                'account' => $accountLabel,
                'custom_id' => $entry->custom_id,
                'difference_amount' => $difference,
                'assigned_by' => $user->name,
            ],
        ];
    }

    private function resolveLineAmount(BankStatementLine $line): float
    {
        if ($line->credit_amount !== null || $line->debit_amount !== null) {
            return (float) ($line->credit_amount ?? 0) - (float) ($line->debit_amount ?? 0);
        }

        return (float) ($line->amount ?? 0);
    }

    private function applyLineUpdates(BankStatementLine $line, array $updates): void
    {
        $allowed = Arr::only($updates, ['custom_identifier', 'billing_reference_date']);

        if (empty($allowed)) {
            return;
        }

        if (array_key_exists('custom_identifier', $allowed)) {
            $line->custom_identifier = $allowed['custom_identifier'];
        }

        if (array_key_exists('billing_reference_date', $allowed)) {
            $line->billing_reference_date = $allowed['billing_reference_date'];
        }

        $line->save();
    }

    private function applyEntryUpdates(SalesBookEntry $entry, array $updates): void
    {
        $allowed = Arr::only($updates, ['custom_id', 'bank_name', 'recorded_date', 'operation_reference']);

        if (empty($allowed)) {
            return;
        }

        foreach ($allowed as $key => $value) {
            $entry->{$key} = $value;
        }

        $entry->save();
    }
}
