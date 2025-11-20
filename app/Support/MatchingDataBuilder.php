<?php

namespace App\Support;

use App\Models\BankStatementLine;
use App\Models\PaymentVoucher;
use App\Models\Transaction;
use App\Support\ReconciliationSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchingDataBuilder
{
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

        $bankList = $banks->map(function ($bank) {
            return [
                'id' => (string) $bank->id,
                'name' => $bank->name,
                'short_code' => $bank->short_code,
            ];
        })->values();

        $voucherCandidates = PaymentVoucher::with('student')
            ->where('status', '!=', 'conciliado')
            ->latest('received_at')
            ->take(150)
            ->get();

        $voucherOperationMap = $voucherCandidates
            ->filter(fn ($voucher) => filled($voucher->operation_number))
            ->groupBy(fn ($voucher) => $voucher->operation_number);

        $transactions = BankStatementLine::with([
            'statement.bank',
            'transaction.student',
        ])
            ->latest('operation_date')
            ->take(100)
            ->get()
            ->map(function (BankStatementLine $line) use ($voucherOperationMap, $differenceThreshold, $shortageThreshold) {
                $status = 'pending';
                $alert = null;
                $suggestion = null;
                $difference = 0;

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

                return [
                    'id' => 'tx-'.$line->id,
                    'db_id' => $line->id,
                    'studentId' => $line->transaction?->student_id,
                    'student' => $line->transaction?->student?->full_name
                        ?? $suggestion?->student?->full_name
                        ?? 'Por asignar',
                    'enrollment' => $line->transaction?->student?->code
                        ?? $suggestion?->student?->code
                        ?? '—',
                    'bankId' => optional($line->statement?->bank)->id,
                    'amount' => (float) $line->amount,
                    'reference' => $line->reference ?? $line->operation_number ?? '—',
                    'date' => optional($line->operation_date)?->toDateTimeString()
                        ?? now()->toDateTimeString(),
                    'status' => $status,
                    'channel' => 'transferencia',
                    'alert' => $alert,
                    'operation_number' => $line->operation_number,
                    'difference' => round($difference, 2),
                    'suggestedVoucherId' => $suggestion?->id,
                ];
            })
            ->values();

        $vouchers = $voucherCandidates->map(function (PaymentVoucher $voucher) {
            return [
                'id' => 'vc-'.$voucher->id,
                'db_id' => $voucher->id,
                'studentId' => $voucher->student_id,
                'student' => $voucher->student?->full_name ?? 'Sin estudiante',
                'enrollment' => $voucher->student?->code ?? '—',
                'amount' => (float) $voucher->amount,
                'issueDate' => optional($voucher->paid_at)?->toDateString() ?? now()->toDateString(),
                'dueDate' => optional($voucher->paid_at)?->toDateString() ?? now()->toDateString(),
                'status' => $voucher->status,
                'bankId' => $voucher->bank_id,
                'operation_number' => $voucher->operation_number,
            ];
        })->values();

        $latestReconciliations = Transaction::with([
            'voucher.student',
            'line.statement.bank',
        ])
            ->latest('matched_at')
            ->take(30)
            ->get()
            ->map(function (Transaction $transaction) {
                $line = $transaction->line;

                return [
                    'id' => $transaction->id,
                    'transactionId' => $line?->id,
                    'voucherId' => $transaction->payment_voucher_id,
                    'bankId' => optional($line?->statement?->bank)->id,
                    'student' => $transaction->voucher?->student?->full_name ?? 'Sin estudiante',
                    'amount' => (float) ($line?->amount ?? $transaction->voucher?->amount ?? 0),
                    'date' => optional($transaction->matched_at ?? $line?->operation_date)?->toDateString()
                        ?? now()->toDateString(),
                    'status' => ucfirst($transaction->status ?? 'matched'),
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
                'program' => $student->program ?? '—',
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
}
