<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Http\Request;

class ReconciliationReviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole(User::ROLE_JEFE_CONTABILIDAD), 403);

        $filters = [
            'status' => $request->input('status'),
            'bank_id' => $request->input('bank_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $query = PaymentVoucher::with(['student', 'bankAccount.bank', 'transaction'])
            ->whereIn('status', ['conciliado', 'rechazado', 'demasia'])
            ->latest('updated_at');

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['bank_id']) {
            $query->whereHas('bankAccount', function ($q) use ($filters) {
                $q->where('bank_id', $filters['bank_id']);
            });
        }

        if ($filters['start_date']) {
            $query->whereDate('paid_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('paid_at', '<=', $filters['end_date']);
        }

        $vouchers = $query->paginate(25)->withQueryString();

        return view('admin.reconciliations.index', [
            'vouchers' => $vouchers,
            'filters' => $filters,
            'banks' => Bank::orderBy('name')->get(),
            'availableStatuses' => [
                'conciliado' => 'Conciliado',
                'demasia' => 'Pago en demasía',
                'rechazado' => 'Rechazado',
            ],
        ]);
    }
}
