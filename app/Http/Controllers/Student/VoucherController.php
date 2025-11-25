<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(User::ROLE_ESTUDIANTE), 403);

        $validated = $request->validate([
            'student_code' => ['required', 'string'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'payment_type' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'operation_number' => ['required', 'string', 'max:80'],
            'account_reference' => ['nullable', 'string', 'max:80'],
            'voucher_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $student = Student::where('code', $validated['student_code'])
            ->orWhere('email', $user->email)
            ->first();

        abort_unless($student, 422, 'No se encontró tu registro de estudiante.');

        if (($validated['bank_account_id'] ?? null) && $validated['bank_id']) {
            abort_unless(
                BankAccount::where('id', $validated['bank_account_id'])
                    ->where('bank_id', $validated['bank_id'])
                    ->exists(),
                422,
                'La cuenta bancaria no pertenece al banco seleccionado.'
            );
        }

        $path = $request->file('voucher_file')->store('vouchers', 'public');

        PaymentVoucher::create([
            'voucher_batch_id' => null,
            'student_id' => $student->id,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
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
            'document_mime' => $request->file('voucher_file')->getClientMimeType(),
            'raw_payload' => [
                'uploaded_by' => $user->email,
                'source' => 'student_portal',
            ],
        ]);

        return back()->with('status', 'Voucher enviado correctamente.');
    }
}
