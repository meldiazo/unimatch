<?php

namespace App\Support;

use App\Models\BankStatementLine;
use App\Models\PaymentVoucher;
use App\Models\Transaction;
use App\Support\ReconciliationSettings;
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

        $voucherCandidates = PaymentVoucher::with('student')
            ->whereNotIn('status', ['conciliado', 'demasia', 'rechazado'])
            ->latest('received_at')
            ->take(150)
            ->get();

        $voucherOperationMap = $voucherCandidates
            ->filter(fn ($voucher) => filled($voucher->operation_number))
            ->groupBy(fn ($voucher) => $voucher->operation_number);

        $transactions = BankStatementLine::with([
            'statement.account.bank',
            'transaction.voucher.student',
        ])
            ->whereDoesntHave('transaction')
            ->latest('operation_date')
            ->take(100)
            ->get()
            ->map(function (BankStatementLine $line) use ($voucherOperationMap, $differenceThreshold, $shortageThreshold) {
                $status = 'pending';
                $alert = null;
                $suggestion = null;
                $difference = 0;
                $bank = optional($line->statement?->account?->bank);
                $voucher = $line->transaction?->voucher;

                if ($line->transaction) {
                    $status = $line->transaction->status ?? 'matched';
                    $difference = (float) ($line->transaction->difference_amount ?? 0);
                } elseif ($line->operation_number && $voucherOperationMap->has($line->operation_number)) {
                    $status = 'suggested';
                    $suggestion = $voucherOperationMap->get($line->operation_number)->first();
                    $difference = $suggestion
                        ? (float) $line->amount - (float) $suggestion->amount
                        : 0;

                    if (abs($difference) >= $differenceThreshold) {
                        $alert = $difference > 0
                            ? 'El extracto tiene un monto mayor que el voucher.'
                            : 'El voucher es mayor al monto del extracto.';
                        $status = 'flagged';
                    }

                    if ($difference < 0 && abs($difference) >= $shortageThreshold) {
                        $alert = 'El voucher es menor al monto del extracto.';
                        $status = 'flagged';
                    }
                }

                $studentName = $this->extractStudentName($voucher)
                    ?? $this->extractStudentName($suggestion)
                    ?? 'Por asignar';

                $studentCode = $this->extractStudentCode($voucher)
                    ?? $this->extractStudentCode($suggestion)
                    ?? '—';

                $detailText = $line->description
                    ?? $line->reference
                    ?? $line->operation_number
                    ?? '—';

                return [
                    'id' => 'tx-'.$line->id,
                    'db_id' => $line->id,
                    'studentId' => $voucher?->student_id
                        ?? $suggestion?->student_id,
                    'student' => $studentName,
                    'student_name' => $studentName,
                    'enrollment' => $studentCode,
                    'student_code' => $studentCode,
                    'bankId' => $bank->id ? (string) $bank->id : null,
                    'bankName' => $bank->name,
                    'amount' => (float) $line->amount,
                    'reference' => $detailText,
                    'date' => optional($line->operation_date)?->toDateTimeString()
                        ?? now()->toDateTimeString(),
                    'transaction_time' => optional($line->transaction_time)?->format('H:i:s'),
                    'status' => $status,
                    'channel' => 'transferencia',
                    'alert' => $alert,
                    'operation_number' => $line->operation_number,
                    'difference' => round($difference, 2),
                    'suggestedVoucherId' => $suggestion?->id,
                    'billing_status' => $voucher?->billing_status
                        ?? $suggestion?->billing_status
                        ?? 'pendiente',
                    'office' => $line->office,
                    'debit_amount' => $line->debit_amount !== null
                        ? (float) $line->debit_amount
                        : ($line->amount < 0 ? abs((float) $line->amount) : 0),
                    'credit_amount' => $line->credit_amount !== null
                        ? (float) $line->credit_amount
                        : ($line->amount > 0 ? (float) $line->amount : 0),
                    'running_balance' => $line->running_balance !== null ? (float) $line->running_balance : null,
                ];
            })
            ->values();

        $vouchers = $voucherCandidates->map(function (PaymentVoucher $voucher) {
            $bank = optional($voucher->bankAccount?->bank ?? $voucher->bank);
            $studentName = $this->extractStudentName($voucher) ?? 'Sin estudiante';
            $studentCode = $this->extractStudentCode($voucher) ?? '—';
            $paymentDate = optional($voucher->paid_at)?->toDateString() ?? now()->toDateString();

            return [
                'id' => 'vc-'.$voucher->id,
                'db_id' => $voucher->id,
                'studentId' => $voucher->student_id,
                'student' => $studentName,
                'student_name' => $studentName,
                'enrollment' => $studentCode,
                'student_code' => $studentCode,
                'amount' => (float) $voucher->amount,
                'issueDate' => $paymentDate,
                'dueDate' => null,
                'paymentDate' => $paymentDate,
                'status' => $voucher->status,
                'bankId' => $bank->id ? (string) $bank->id : null,
                'bankName' => $bank->name,
                'operation_number' => $voucher->operation_number,
                'billing_status' => $voucher->billing_status ?? 'pendiente',
            ];
        })->values();

        $latestReconciliations = Transaction::with([
            'voucher.student',
            'line.statement.account.bank',
        ])
            ->latest('matched_at')
            ->take(30)
            ->get()
            ->map(function (Transaction $transaction) {
                $line = $transaction->line;
                $student = $transaction->voucher?->student;
                $studentCode = $student?->code
                    ?? ($transaction->voucher?->raw_payload['student_code'] ?? null);

                return [
                    'id' => $transaction->id,
                    'transactionId' => $line?->id,
                    'voucherId' => $transaction->payment_voucher_id,
                    'bankId' => optional($line?->statement?->account?->bank)->id,
                    'student' => $student?->full_name ?? 'Sin estudiante',
                    'student_name' => $student?->full_name ?? 'Sin estudiante',
                    'student_code' => $studentCode,
                    'amount' => (float) ($line?->amount ?? $transaction->voucher?->amount ?? 0),
                    'date' => optional($transaction->matched_at ?? $line?->operation_date)?->toDateString()
                        ?? now()->toDateString(),
                    'status' => strtolower($transaction->status ?? 'conciliado'),
                    'billing_status' => $transaction->voucher?->billing_status ?? 'pendiente',
                ];
            })
            ->values();

        $latestPayments = PaymentVoucher::select('student_id', DB::raw('MAX(paid_at) as last_paid_at'))
            ->groupBy('student_id')
            ->pluck('last_paid_at', 'student_id');

        $studentRows = $students->map(function ($student) use ($latestPayments) {
            $lastPayment = $latestPayments->get($student->id);

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
            'vouchers' => $vouchers,
            'reconciliations' => $latestReconciliations,
            'students' => $studentRows,
        ];
    }

    private function extractStudentName(?PaymentVoucher $voucher): ?string
    {
        if (! $voucher) {
            return null;
        }

        if ($voucher->student?->full_name) {
            return $voucher->student->full_name;
        }

        $identifier = $voucher->student?->code
            ?? ($voucher->raw_payload['student_code'] ?? null);

        if ($identifier) {
            $student = $this->findStudentByIdentifier($identifier);
            if ($student) {
                return $student->full_name ?? $student->code;
            }

            if (str_contains($identifier, '@')) {
                return Str::of($identifier)
                    ->before('@')
                    ->replace(['.', '_', '-'], ' ')
                    ->title()
                    ->trim()
                    ->value() ?: $identifier;
            }
        }

        return $voucher->student?->code ?? $identifier;
    }

    private function extractStudentCode(?PaymentVoucher $voucher): ?string
    {
        if (! $voucher) {
            return null;
        }

        if ($voucher->student?->code) {
            return $voucher->student->code;
        }

        $identifier = $voucher->raw_payload['student_code'] ?? null;
        if ($identifier) {
            $student = $this->findStudentByIdentifier($identifier);
            if ($student) {
                return $student->code;
            }

            return $identifier;
        }

        return null;
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
