<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BillingStatusController extends Controller
{
    public function update(Request $request, PaymentVoucher $voucher)
    {
        abort_unless($request->user()->hasRole(User::ROLE_CAJERO), 403);

        $validated = $request->validate([
            'billing_status' => ['required', 'in:pendiente,facturado'],
        ]);

        if ($validated['billing_status'] !== 'facturado') {
            throw ValidationException::withMessages([
                'billing_status' => 'Solo puedes marcar como facturado desde esta pantalla.',
            ]);
        }

        if ($voucher->billing_status !== 'pendiente') {
            throw ValidationException::withMessages([
                'billing_status' => 'Este voucher ya fue actualizado.',
            ]);
        }

        if (! in_array($voucher->status, ['conciliado', 'demasia'], true)) {
            throw ValidationException::withMessages([
                'billing_status' => 'Solo se puede facturar un voucher conciliado o marcado como demasía.',
            ]);
        }

        $voucher->update([
            'billing_status' => 'facturado',
            'billed_at' => now(),
            'billed_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Estado de facturación actualizado.');
    }
}
