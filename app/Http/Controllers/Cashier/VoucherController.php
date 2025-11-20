<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\PaymentVoucher;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn($request, $next) => 
            $request->user()->hasRole(User::ROLE_CAJERO) ? $next($request) : abort(403)
        );
    }

    public function downloadCertificate(PaymentVoucher $voucher)
    {
        $voucher->load(['student', 'bank', 'transaction']);

        $pdf = Pdf::loadView('documents.voucher-certificate', compact('voucher'))
            ->setPaper('a4')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10);

        return $pdf->download(
            "constancia-{$voucher->student->code}-{$voucher->id}.pdf"
        );
    }
}