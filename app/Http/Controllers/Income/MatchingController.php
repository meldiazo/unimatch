<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\StudentBalance;
use App\Models\ReconciliationSetting as ReconciliationSettingModel;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ReconciliationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]), 403);

        $validated = $request->validate([
            'action' => ['required', 'in:confirm,reject,credit'],
            'bank_statement_line_id' => ['required', 'exists:bank_statement_lines,id'],
            'payment_voucher_id' => ['required', 'exists:payment_vouchers,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'credit_amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $settings = app(ReconciliationSettings::class)->current();

        $line = BankStatementLine::with('transaction')->findOrFail($validated['bank_statement_line_id']);
        $voucher = PaymentVoucher::with('transaction', 'student')->findOrFail($validated['payment_voucher_id']);

        $action = $validated['action'];

        return match ($action) {
            'reject' => $this->rejectMatch($line, $voucher, $validated['reason'] ?? null),
            'credit' => $this->creditMatch($line, $voucher, $validated, $request->user(), $settings),
            default => $this->confirmMatch($line, $voucher, $request->user()),
        };
    }

    private function confirmMatch(BankStatementLine $line, PaymentVoucher $voucher, User $user): JsonResponse
    {
        if ($response = $this->ensurePending($line, $voucher)) {
            return $response;
        }

        $payload = $this->finalizeMatch($line, $voucher, $user);

        return response()->json(array_merge($payload, [
            'action' => 'confirm',
            'message' => 'Coincidencia confirmada.',
        ]));
    }

    private function creditMatch(BankStatementLine $line, PaymentVoucher $voucher, array $validated, User $user, ReconciliationSettingModel $settings): JsonResponse
    {
        if ($response = $this->ensurePending($line, $voucher)) {
            return $response;
        }

        $difference = (float) $line->amount - (float) $voucher->amount;
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

        $payload = $this->finalizeMatch($line, $voucher, $user, 'demasia', 'Pago en demasía');

        $studentId = $this->resolveVoucherStudent($voucher);
        if (! $studentId) {
            return response()->json([
                'message' => 'No se pudo identificar al estudiante para acreditar la demasía.',
            ], 422);
        }

        $balance = StudentBalance::firstOrCreate(
            [
                'student_id' => $studentId,
                'currency' => $voucher->currency ?? 'BOB',
            ],
            ['balance_amount' => 0]
        );

        $balance->increment('balance_amount', $creditAmount);

        return response()->json(array_merge($payload, [
            'action' => 'credit',
            'credit_amount' => round($creditAmount, 2),
            'message' => 'Pago registrado como demasía y saldo acreditado al estudiante.',
        ]));
    }

    private function rejectMatch(BankStatementLine $line, PaymentVoucher $voucher, ?string $reason): JsonResponse
    {
        $voucher->update([
            'status' => 'rechazado',
            'reason' => $reason ?: 'Rechazado en conciliación',
        ]);

        return response()->json([
            'action' => 'reject',
            'transaction' => [
                'id' => 'tx-'.$line->id,
                'db_id' => $line->id,
                'status' => 'pending',
            ],
            'voucher' => [
                'id' => 'vc-'.$voucher->id,
                'db_id' => $voucher->id,
                'reason' => $voucher->reason,
            ],
            'message' => 'Voucher marcado como rechazado.',
        ]);
    }

    private function ensurePending(BankStatementLine $line, PaymentVoucher $voucher): ?JsonResponse
    {
        if ($line->transaction) {
            return response()->json([
                'message' => 'La transacción del extracto ya fue conciliada.',
            ], 422);
        }

        if ($voucher->transaction || $voucher->status === 'conciliado') {
            return response()->json([
                'message' => 'El voucher ya fue conciliado.',
            ], 422);
        }

        return null;
    }

    private function finalizeMatch(BankStatementLine $line, PaymentVoucher $voucher, User $user, string $status = 'conciliado', ?string $reason = null): array
    {
        $line->loadMissing('statement.account.bank');
        $difference = (float) $line->amount - (float) $voucher->amount;
        $studentId = $this->resolveVoucherStudent($voucher);

        $transaction = Transaction::create([
            'bank_statement_line_id' => $line->id,
            'payment_voucher_id' => $voucher->id,
            'student_id' => $studentId,
            'status' => $status,
            'notes' => null,
            'matched_by' => $user->id,
            'matched_at' => now(),
            'difference_amount' => $difference,
        ]);

        $voucher->update([
            'status' => $status,
            'reason' => $reason,
        ]);

        return [
            'transaction' => [
                'id' => 'tx-'.$line->id,
                'db_id' => $line->id,
                'student' => $voucher->student?->full_name ?? 'Sin estudiante',
                'enrollment' => $voucher->student?->code ?? '—',
                'status' => $status,
                'billing_status' => $voucher->billing_status ?? 'pendiente',
            ],
            'voucher' => [
                'id' => 'vc-'.$voucher->id,
                'db_id' => $voucher->id,
            ],
            'reconciliation' => [
                'id' => $transaction->id,
                'transactionId' => $line->id,
                'voucherId' => $voucher->id,
                'bankId' => optional($line->statement?->account?->bank)->id,
                'student' => $voucher->student?->full_name ?? 'Sin estudiante',
                'amount' => (float) $line->amount,
                'date' => now()->toDateString(),
                'status' => $status,
                'billing_status' => $voucher->billing_status ?? 'pendiente',
            ],
        ];
    }
    private function resolveVoucherStudent(PaymentVoucher $voucher): ?int
    {
        if ($voucher->student_id) {
            return $voucher->student_id;
        }

        $identifier = $voucher->student?->code
            ?? ($voucher->student?->email ?? null)
            ?? ($voucher->raw_payload['student_code'] ?? null);

        if (! $identifier) {
            return null;
        }

        $student = Student::query()
            ->where('code', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($student) {
            $voucher->student_id = $student->id;
            $voucher->save();

            return $student->id;
        }

        return null;
    }
}
