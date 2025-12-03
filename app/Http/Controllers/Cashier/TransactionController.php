<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\PaymentVoucher;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn($request, $next) => 
            $request->user()->hasRole(User::ROLE_CAJERO) ? $next($request) : abort(403)
        );
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:80'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'status' => ['nullable', 'in:recibido,validado,demasia,rechazado,conciliado'],
            'billing_status' => ['nullable', 'in:pendiente,facturado'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = PaymentVoucher::with(['student', 'bank', 'transaction'])
            ->latest('paid_at');

        if ($validated['query'] ?? null) {
            $search = mb_strtolower($validated['query']);
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->whereHas('student', function ($sq) use ($like) {
                    $sq->whereRaw('LOWER(full_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                })
                ->orWhereRaw('LOWER(operation_number) LIKE ?', [$like]);
            });
        }

        if ($validated['bank_id'] ?? null) {
            $query->where('bank_id', $validated['bank_id']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['billing_status'] ?? null) {
            $query->where('billing_status', $validated['billing_status']);
        }

        return $query->paginate($validated['per_page'] ?? 20)
            ->through(fn($voucher) => [
                'id' => $voucher->id,
                'student_name' => $voucher->student?->full_name ?? 'Sin estudiante',
                'student_code' => $voucher->student?->code ?? '—',
                'bank' => $voucher->bank?->name ?? '—',
                'amount' => (float) $voucher->amount,
                'operation_number' => $voucher->operation_number,
                'paid_at' => $voucher->paid_at?->format('d/m/Y'),
                'status' => $voucher->status,
                'billing_status' => $voucher->billing_status,
                'transaction_status' => $voucher->transaction?->status ?? 'sin_transaccion',
                'document_path' => $voucher->document_path,
            ]);
    }
}
