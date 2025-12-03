<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\PaymentVoucher;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Reporte de pagos (facturación / caja).
     */
    public function pagos(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]),
            403
        );

        $query = PaymentVoucher::with(['student', 'bankAccount.bank'])
            ->latest('paid_at');

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'bank_id' => $request->input('bank_id'),
            'billing_status' => $request->input('billing_status'),
        ];

        if ($filters['start_date']) {
            $query->whereDate('paid_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('paid_at', '<=', $filters['end_date']);
        }

        if ($filters['bank_id']) {
            $query->whereHas('bankAccount', function ($query) use ($filters) {
                $query->where('bank_id', $filters['bank_id']);
            });
        }

        if ($filters['billing_status']) {
            $query->where('billing_status', $filters['billing_status']);
        }

        $rows = $query->get()->map(function (PaymentVoucher $voucher) {
                $student = $voucher->student;
                $payload = is_array($voucher->raw_payload ?? null) ? $voucher->raw_payload : [];

                return [
                    'num_caja' => Arr::get($payload, 'num_caja', '—'),
                    'fecha_pago_estudiante' => $this->formatDate($voucher->paid_at),
                    'fecha_recepcion' => $this->formatDate($voucher->received_at),
                    'num_factura' => Arr::get($payload, 'num_factura', '—'),
                    'nit_ci' => '—',
                    'razon_social' => '—',
                    'nombre_estudiante' => $student?->full_name ?? 'N/A',
                    'tipo_pago' => $voucher->payment_type ?? 'N/A',
                    'monto' => $voucher->amount,
                    'cuenta' => $voucher->account_reference
                        ?? $voucher->bankAccount->account_number
                        ?? 'N/A',
                    'estado' => ucfirst($voucher->billing_status ?? 'pendiente'),
                    'num_operacion' => $voucher->operation_number ?? 'N/A',
                ];
            });

        $export = strtolower((string) $request->query('export', ''));

        if ($export === 'pdf') {
            return $this->downloadPdf($rows, $request);
        }

        if ($export === 'csv') {
            return $this->downloadCsv($rows);
        }

        return view('reports.facturacion', [
            'rows' => $rows,
            'generatedAt' => now(),
            'banks' => Bank::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return 'N/A';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function downloadPdf($rows, Request $request)
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'rows' => $rows,
            'generatedAt' => now(),
            'user' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-pagos-'.now()->format('Ymd_His').'.pdf');
    }

    private function downloadCsv($rows)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reporte-pagos-'.now()->format('Ymd_His').'.csv"',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Fecha pago estudiante',
                'Fecha recepción',
                'NIT/CI',
                'Razón social',
                'Nombre estudiante',
                'Tipo de pago',
                'Monto',
                'Cuenta',
                'Estado',
                'N° operación',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['fecha_pago_estudiante'],
                    $row['fecha_recepcion'],
                    $row['nit_ci'],
                    $row['razon_social'],
                    $row['nombre_estudiante'],
                    $row['tipo_pago'],
                    $row['monto'],
                    $row['cuenta'],
                    $row['estado'],
                    $row['num_operacion'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
