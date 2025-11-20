<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        'bank_id' => ['required', 'exists:banks,id'],
        'payment_type' => ['required', 'string', 'max:50'],
        'amount' => ['required', 'numeric', 'min:0.01'],
        'paid_at' => ['required', 'date', 'before_or_equal:today'],
        'operation_number' => ['required', 'string', 'max:80', 'unique:payment_vouchers,operation_number'],
        'account_reference' => ['nullable', 'string', 'max:80'],
        'voucher_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ]);

    $student = Student::where('email', $user->email)
        ->orWhere('code', $validated['student_code'])
        ->first();

    abort_unless($student, 422, 'No se encontró tu registro de estudiante.');

    $path = $request->file('voucher_file')->store('vouchers', 'public');
    $mime = $request->file('voucher_file')->getClientMimeType();

    $voucher = PaymentVoucher::create([
        'voucher_batch_id' => null,
        'student_id' => $student->id,
        'bank_id' => $validated['bank_id'],
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
            'uploaded_by' => $user->email,
            'source' => 'student_portal',
            'ip' => $request->ip(),
        ],
    ]);

    return back()
        ->with('status', 'Voucher registrado exitosamente. Será validado en las próximas 24 horas.')
        ->with('voucher_id', $voucher->id);
    }

    public function replace(Request $request, PaymentVoucher $voucher): RedirectResponse
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->firstOrFail();

        abort_unless(
            $voucher->student_id === $student->id && $voucher->status === 'rechazado',
            403,
            'No puedes reemplazar este voucher.'
        );

        $validated = $request->validate([
            'voucher_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Guardar archivo anterior
        $oldPath = $voucher->document_path;

        $path = $request->file('voucher_file')->store('vouchers', 'public');
        $mime = $request->file('voucher_file')->getClientMimeType();

        $voucher->update([
            'status' => 'recibido',
            'document_path' => $path,
            'document_mime' => $mime,
            'reason' => null,
            'raw_payload' => [
                ...($voucher->raw_payload ?? []),
                'replaced_at' => now()->toDateTimeString(),
                'old_document' => $oldPath,
                'replacement_notes' => $validated['notes'] ?? null,
            ],
        ]);

        // Opcionalmente eliminar archivo anterior
        Storage::disk('public')->delete($oldPath);

        return back()->with('status', 'Voucher reemplazado. Será revisado nuevamente.');
    }
}
