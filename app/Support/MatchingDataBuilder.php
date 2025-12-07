<?php

namespace App\Support;

use App\Models\BankStatementLine;
use App\Models\SalesBookEntry;
use App\Models\Transaction;
use App\Models\StudentBalance;
use App\Support\ReconciliationSettings;
use App\Support\StudentBalanceProjector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchingDataBuilder
{
    /** @var array<string, \App\Models\Student> */
    private array $studentsByCode = [];

    /** @var array<string, \App\Models\Student> */
    private array $studentsByEmail = [];

    /**
     * Build dataset consumed by the reconciliation UI.
     *
     * @param  \Illuminate\Support\Collection<int,\App\Models\Bank>  $banks
     * @param  \Illuminate\Support\Collection<int,\App\Models\Student>  $students
     */
    public function build(Collection $banks, Collection $students): array
    {
        app(StudentBalanceProjector::class)->sync();
        $settings = app(ReconciliationSettings::class)->current();
        $differenceThreshold = (float) $settings->difference_alert_threshold;
        $shortageThreshold = (float) $settings->shortage_alert_threshold;

        $this->studentsByCode = $students
            ->filter(fn ($student) => filled($student->code))
            ->keyBy(fn ($student) => strtoupper(trim($student->code)))
            ->all();
        $this->studentsByEmail = $students
            ->filter(fn ($student) => filled($student->email))
            ->keyBy(fn ($student) => strtolower($student->email))
            ->all();

        $bankList = $banks->map(function ($bank) {
            return [
                'id' => (string) $bank->id,
                'name' => $bank->name,
                'short_code' => $bank->short_code,
            ];
        })->values();

        $reportEntries = SalesBookEntry::with('transaction')
            ->whereDoesntHave('transaction')
            ->latest('invoice_date')
            ->latest('id')
            ->take(200)
            ->get();

        $overpayments = StudentBalance::with('student')
            ->where('balance_amount', '>', 0)
            ->latest('updated_at')
            ->get()
            ->map(function (StudentBalance $balance) {
                $student = $balance->student;

                return [
                    'id' => 'sb-'.$balance->id,
                    'student_name' => $student?->full_name ?? 'Sin estudiante',
                    'student_code' => $student?->code ?? '—',
                    'balance' => (float) $balance->balance_amount,
                    'credited_at' => optional($balance->updated_at)?->toDateTimeString(),
                ];
            })
            ->values();

        $entryOperationMap = $reportEntries
            ->filter(fn ($entry) => filled($entry->operation_reference))
            ->groupBy(fn ($entry) => $this->normalizeOperationKey($entry->operation_reference));

        $transactions = BankStatementLine::with([
            'statement.account.bank',
            'transaction.salesEntry',
        ])
            ->whereDoesntHave('transaction')
            ->latest('operation_date')
            ->take(100)
            ->get()
            ->map(function (BankStatementLine $line) use ($entryOperationMap, $differenceThreshold, $shortageThreshold, $reportEntries) {
                $status = 'pending';
                $alert = null;
                $suggestion = null;
                $difference = 0;
                $bank = optional($line->statement?->account?->bank);
                $lineAmount = $this->resolveLineAmount($line);
                $entry = $line->transaction?->salesEntry;

                if ($line->operation_number) {
                    $normalizedOperation = $this->normalizeOperationKey($line->operation_number);
                    if ($normalizedOperation && $entryOperationMap->has($normalizedOperation)) {
                        $suggestion = $entryOperationMap->get($normalizedOperation)->first();
                    }
                }

                if (! $suggestion && ! $entry) {
                    $suggestion = $this->findClosestEntryMatch($reportEntries, $lineAmount, $line->operation_date, $differenceThreshold);
                }

                if ($suggestion && ! $entry) {
                    $status = 'suggested';
                    $difference = $lineAmount - (float) $suggestion->amount;

                    if (abs($difference) >= $differenceThreshold && $difference !== 0.0) {
                        $alert = $difference > 0
                            ? 'El extracto tiene un monto mayor que el registro diario.'
                            : 'El registro diario tiene un monto mayor que el extracto.';
                        $status = 'flagged';
                    }

                    if ($difference < 0 && abs($difference) >= $shortageThreshold) {
                        $alert = 'El registro diario es menor al monto del extracto.';
                        $status = 'flagged';
                    }
                }

                $studentName = $this->resolveEntryName($entry ?? $suggestion);
                $studentCode = $this->resolveEntryCode($entry ?? $suggestion);
                $entryStatus = $entry?->state_label ?? $suggestion?->state_label ?? 'pendiente';

                $detailText = $line->description
                    ?? $line->reference
                    ?? $line->operation_number
                    ?? '—';

                return [
                    'id' => 'tx-'.$line->id,
                    'db_id' => $line->id,
                    'update_url' => route('ingresos.statements.update', $line),
                    'studentId' => null,
                    'student' => $studentName,
                    'student_name' => $studentName,
                    'enrollment' => $studentCode,
                    'student_code' => $studentCode,
                    'bankId' => $bank->id ? (string) $bank->id : null,
                    'bankName' => $bank->name,
                    'amount' => (float) $lineAmount,
                    'reference' => $detailText,
                    'date' => optional($line->operation_date)?->toDateTimeString()
                        ?? now()->toDateTimeString(),
                    'transaction_time' => optional($line->transaction_time)?->format('H:i:s'),
                    'status' => $status,
                    'channel' => 'transferencia',
                    'alert' => $alert,
                    'operation_number' => $line->operation_number,
                    'difference' => round($difference, 2),
                    'suggestedEntryId' => $suggestion?->id,
                    'billing_status' => $entryStatus,
                    'office' => $line->office,
                    'line_number' => $line->line_number,
                    'debit_amount' => $line->debit_amount !== null
                        ? (float) $line->debit_amount
                        : ($line->amount < 0 ? abs((float) $line->amount) : 0),
                    'credit_amount' => $line->credit_amount !== null
                        ? (float) $line->credit_amount
                        : ($line->amount > 0 ? (float) $line->amount : 0),
                    'running_balance' => $line->running_balance !== null ? (float) $line->running_balance : null,
                    'custom_id' => $line->custom_identifier,
                    'billing_reference_date' => optional($line->billing_reference_date)?->toDateString(),
                    'value_date' => optional($line->value_date)?->toDateString(),
                    'entry_bank' => $suggestion?->bank_name,
                    'entry_operation' => $suggestion?->operation_reference,
                    'bank_name' => $bank->name,
                ];
            })
            ->values();

        $reportEntriesData = $reportEntries->map(function (SalesBookEntry $entry) {
            return [
                'id' => 'sr-'.$entry->id,
                'db_id' => $entry->id,
                'update_url' => route('ingresos.sales-report.update', $entry),
                'student' => $entry->student_name ?? 'Sin estudiante',
                'student_name' => $entry->student_name ?? 'Sin estudiante',
                'enrollment' => $entry->custom_id ?? '—',
                'amount' => (float) $entry->amount,
                'issueDate' => optional($entry->invoice_date)?->toDateString(),
                'recorded_date' => optional($entry->recorded_date)?->toDateString(),
                'status' => $entry->state_label ?? 'pendiente',
                'bankName' => $entry->bank_name,
                'bank_name' => $entry->bank_name,
                'operation_number' => $entry->operation_reference,
                'operation_reference' => $entry->operation_reference,
                'row_number' => $entry->row_number,
                'custom_id' => $entry->custom_id,
                'nit_ci' => $entry->nit_ci,
                'razon_social' => $entry->razon_social,
                'payment_type' => $entry->payment_type,
                'invoice_number' => $entry->invoice_number,
                'account' => $entry->account_label,
                'state_label' => $entry->state_label,
                'invoice_number' => $entry->invoice_number,
                'invoice_date' => optional($entry->invoice_date)?->toDateString(),
                'nit_ci' => $entry->nit_ci,
                'razon_social' => $entry->razon_social,
            ];
        })->values();

        $latestReconciliations = Transaction::with([
            'salesEntry',
            'line.statement.account.bank',
            'matchedBy',
        ])
            ->latest('matched_at')
            ->take(30)
            ->get()
            ->map(function (Transaction $transaction) {
                $line = $transaction->line;
                $entry = $transaction->salesEntry;
                $studentName = $entry?->student_name ?? 'Sin estudiante';
                $studentCode = $entry?->custom_id ?? '—';

                return [
                    'id' => $transaction->id,
                    'transactionId' => $line?->id,
                    'entryId' => $transaction->sales_book_entry_id,
                    'bankId' => optional($line?->statement?->account?->bank)->id,
                    'bank_name' => $entry?->bank_name ?? optional($line?->statement?->account?->bank)->name,
                    'student' => $studentName,
                    'student_name' => $studentName,
                    'student_code' => $studentCode,
                    'amount' => (float) ($line?->amount ?? $entry?->amount ?? 0),
                    'date' => optional($transaction->matched_at ?? $line?->operation_date)?->toDateString()
                        ?? now()->toDateString(),
                    'report_date' => optional($entry?->invoice_date)?->toDateString(),
                    'reconciliation_date' => optional($transaction->matched_at)?->toDateTimeString(),
                    'status' => strtolower($transaction->status ?? 'conciliado'),
                    'billing_status' => $entry?->state_label ?? 'pendiente',
                    'operation_reference' => $entry?->operation_reference ?? $line?->operation_number,
                    'invoice_number' => $entry?->invoice_number,
                    'nit_ci' => $entry?->nit_ci,
                    'razon_social' => $entry?->razon_social,
                    'payment_type' => $entry?->payment_type,
                    'account' => $entry?->account_label ?? optional($line?->statement?->account)->number,
                    'custom_id' => $entry?->custom_id,
                    'difference_amount' => (float) ($transaction->difference_amount ?? 0),
                    'assigned_by' => $transaction->matchedBy?->name,
                ];
            })
            ->values();

        $latestByCode = SalesBookEntry::select('custom_id', DB::raw('MAX(invoice_date) as last_invoice'))
            ->whereNotNull('custom_id')
            ->groupBy('custom_id')
            ->pluck('last_invoice', 'custom_id');

        $latestByName = SalesBookEntry::select('student_name', DB::raw('MAX(invoice_date) as last_invoice'))
            ->whereNotNull('student_name')
            ->groupBy('student_name')
            ->pluck('last_invoice', 'student_name');

        $studentRows = $students->map(function ($student) use ($latestByCode, $latestByName) {
            $lastPayment = null;
            if ($student->code && $latestByCode->has($student->code)) {
                $lastPayment = $latestByCode->get($student->code);
            } elseif ($student->full_name && $latestByName->has($student->full_name)) {
                $lastPayment = $latestByName->get($student->full_name);
            }

            return [
                'id' => 'st-'.$student->id,
                'name' => $student->full_name,
                'enrollment' => $student->code,
                'lastPayment' => $lastPayment ?? optional($student->updated_at)?->toDateString() ?? now()->toDateString(),
                'status' => 'pending',
            ];
        })->values();

        return [
            'banks' => $bankList,
            'transactions' => $transactions,
            'report_entries' => $reportEntriesData,
            'reconciliations' => $latestReconciliations,
            'students' => $studentRows,
            'overpayments' => $overpayments,
        ];
    }

    private function resolveEntryName(?SalesBookEntry $entry): string
    {
        if (! $entry) {
            return 'Por asignar';
        }

        if ($entry->student_name) {
            return $entry->student_name;
        }

        if ($entry->razon_social) {
            return $entry->razon_social;
        }

        $identifier = $entry->custom_id ?? $entry->invoice_number;
        if ($identifier) {
            $student = $this->findStudentByIdentifier($identifier);
            if ($student) {
                return $student->full_name ?? $student->code;
            }
        }

        return 'Por asignar';
    }

    private function resolveEntryCode(?SalesBookEntry $entry): string
    {
        if (! $entry) {
            return '—';
        }

        if ($entry->custom_id) {
            return $entry->custom_id;
        }

        $candidate = $entry->invoice_number ?? $entry->operation_reference;
        if ($candidate) {
            return (string) $candidate;
        }

        return '—';
    }

    private function normalizeOperationKey(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return strtoupper(Str::of($value)->replace([' ', "\t", "\n"], '')->trim()->value());
    }

    private function resolveLineAmount(BankStatementLine $line): float
    {
        if ($line->credit_amount !== null || $line->debit_amount !== null) {
            return (float) ($line->credit_amount ?? 0) - (float) ($line->debit_amount ?? 0);
        }

        return (float) ($line->amount ?? 0);
    }

    private function findClosestEntryMatch(Collection $entries, float $amount, ?Carbon $operationDate, float $threshold): ?SalesBookEntry
    {
        $candidates = $entries->filter(function (SalesBookEntry $entry) use ($amount, $threshold) {
            return abs((float) $entry->amount - $amount) <= max($threshold, 1);
        });

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($operationDate) {
            $operationTimestamp = $operationDate->timestamp;
            return $candidates->sortBy(function (SalesBookEntry $entry) use ($operationTimestamp) {
                $entryDate = $entry->invoice_date ? strtotime($entry->invoice_date) : null;
                return $entryDate !== null ? abs($entryDate - $operationTimestamp) : PHP_INT_MAX;
            })->first();
        }

        return $candidates->first();
    }

    private function findStudentByIdentifier(?string $identifier)
    {
        if (! $identifier) {
            return null;
        }

        $trimmed = trim((string) $identifier);
        if ($trimmed === '') {
            return null;
        }

        $codeKey = strtoupper($trimmed);
        if (isset($this->studentsByCode[$codeKey])) {
            return $this->studentsByCode[$codeKey];
        }

        $lower = strtolower($trimmed);
        return $this->studentsByEmail[$lower] ?? null;
    }
}
