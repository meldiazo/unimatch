<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentBalance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(User::ROLE_ESTUDIANTE), 403);

        $student = Student::where('email', $user->email)->firstOrFail();

        $balance = StudentBalance::firstOrCreate(
            ['student_id' => $student->id, 'currency' => 'BOB'],
            ['balance_amount' => 0]
        );

        // Historial de movimientos (transacciones con saldo)
        $movements = $student->transactions()
            ->with(['line', 'voucher'])
            ->where('difference_amount', '!=', 0)
            ->latest('matched_at')
            ->take(10)
            ->get()
            ->map(function ($tx) {
                return [
                    'date' => $tx->matched_at?->format('d/m/Y H:i'),
                    'description' => $tx->difference_amount > 0 
                        ? "Pago en demasía - Voucher #{$tx->payment_voucher_id}"
                        : "Ajuste - Transacción #{$tx->bank_statement_line_id}",
                    'amount' => (float) $tx->difference_amount,
                    'type' => $tx->difference_amount > 0 ? 'credit' : 'debit',
                ];
            });

        return response()->json([
            'balance' => (float) $balance->balance_amount,
            'currency' => $balance->currency,
            'movements' => $movements,
            'updated_at' => $balance->updated_at->format('d/m/Y H:i:s'),
        ]);
    }
}