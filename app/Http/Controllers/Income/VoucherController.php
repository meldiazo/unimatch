<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]), 403);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'bank_id' => ['required', 'exists:banks,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_type' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'operation_number' => ['required', 'string', 'max:80'],
            'account_reference' => ['nullable', 'string', 'max:80'],
            'voucher_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        if ($validated['bank_account_id'] ?? null) {
            abort_unless(
                BankAccount::where('id', $validated['bank_account_id'])
                    ->where('bank_id', $validated['bank_id'])
                    ->exists(),
                422,
                'La cuenta bancaria no pertenece al banco seleccionado.'
            );
        }

        $duplicateOperation = PaymentVoucher::query()
            ->where('operation_number', $validated['operation_number'])
            ->where('status', '!=', 'rechazado')
            ->where('bank_account_id', $validated['bank_account_id'])
            ->exists();

        abort_if($duplicateOperation, 422, 'Ya existe un voucher con ese número de operación para esta cuenta.');

        $path = null;
        $mime = null;
        if ($request->hasFile('voucher_file')) {
            $path = $request->file('voucher_file')->store('vouchers', 'public');
            $mime = $request->file('voucher_file')->getClientMimeType();
        }

        PaymentVoucher::create([
            'voucher_batch_id' => null,
            'student_id' => $validated['student_id'],
            'bank_id' => $validated['bank_id'],
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'cashbox_number' => null,
            'payment_type' => $validated['payment_type'],
            'operation_number' => $validated['operation_number'],
            'account_reference' => $validated['account_reference'] ?? null,
            'amount' => $validated['amount'],
            'currency' => 'BOB',
            'paid_at' => $validated['paid_at'],
            'received_at' => now(),
            'status' => 'recibido',
            'billing_status' => 'pendiente',
            'document_path' => $path,
            'document_mime' => $mime,
            'raw_payload' => [
                'uploaded_by' => $request->user()->email,
                'source' => 'ingresos_manual',
            ],
        ]);

        return back()->with('status', 'Voucher registrado manualmente.');
    }

    public function update(Request $request, PaymentVoucher $voucher): RedirectResponse
    {
        abort_unless($request->user()->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:40'],
        ]);

        $voucher->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Estado actualizado.');
    }
}
