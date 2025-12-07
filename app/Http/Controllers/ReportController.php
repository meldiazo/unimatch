<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\PaymentVoucher;
use App\Models\SalesBookEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Reporte diario consolidado (facturación / descargas).
     */
    public function pagos(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole([User::ROLE_ENCARGADO_INGRESOS, User::ROLE_JEFE_CONTABILIDAD]),
            403
        );

        $voucherQuery = PaymentVoucher::with(['student', 'bankAccount.bank'])
            ->latest('paid_at');

        $banks = Bank::orderBy('name')->get();
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'bank_id' => $request->input('bank_id'),
            'billing_status' => $request->input('billing_status'),
        ];

        if ($filters['start_date']) {
            $voucherQuery->whereDate('paid_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $voucherQuery->whereDate('paid_at', '<=', $filters['end_date']);
        }

        if ($filters['bank_id']) {
            $voucherQuery->whereHas('bankAccount', function ($query) use ($filters) {
                $query->where('bank_id', $filters['bank_id']);
            });
        }

        if ($filters['billing_status']) {
            $voucherQuery->where('billing_status', $filters['billing_status']);
        }

        $salesQuery = SalesBookEntry::query()->latest('invoice_date');

        if ($filters['start_date']) {
            $salesQuery->whereDate('invoice_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $salesQuery->whereDate('invoice_date', '<=', $filters['end_date']);
        }

        if ($filters['bank_id']) {
            $bankFilter = $banks->firstWhere('id', (int) $filters['bank_id']);
            if ($bankFilter) {
                $salesQuery->where('bank_name', 'like', '%'.$bankFilter->name.'%');
            }
        }

        if ($filters['billing_status']) {
            $salesQuery->where('state_label', 'like', '%'.$filters['billing_status'].'%');
        }

        $salesRows = $salesQuery->get()->map(function (SalesBookEntry $entry) {
                return [
                    'nro' => $entry->legacy_number ?? '—',
                    'fecha' => $this->formatDate($entry->invoice_date),
                    'numero_factura' => $entry->invoice_number ?? '—',
                    'nit_ci' => $entry->nit_ci ?? '—',
                    'razon_social' => $entry->razon_social ?? '—',
                    'nombre_estudiante' => $entry->student_name ?? '—',
                    'tipo_pago' => $entry->payment_type ?? '—',
                    'monto' => $entry->amount,
                    'cuenta' => $entry->account_label ?? '—',
                    'estado' => $entry->state_label ?? '—',
                    'custom_id' => $entry->custom_id ?? ('LV-'.$entry->id),
                    'banco' => $entry->bank_name ?? '—',
                    'fecha_registro' => $this->formatDate($entry->recorded_date ?? $entry->invoice_date),
                    'operation_reference' => $entry->operation_reference ?? '—',
                ];
            });

        $voucherRows = $voucherQuery->get()->map(function (PaymentVoucher $voucher) {
                $student = $voucher->student;
                $payload = is_array($voucher->raw_payload ?? null) ? $voucher->raw_payload : [];
                $bank = $voucher->bankAccount?->bank ?? $voucher->bank;
                $account = $voucher->bankAccount?->account_number ?? $voucher->account_reference ?? 'N/A';

                return [
                    'nro' => Arr::get($payload, 'num_caja', '—'),
                    'fecha' => $this->formatDate($voucher->paid_at),
                    'numero_factura' => Arr::get($payload, 'num_factura', '—'),
                    'nit_ci' => Arr::get($payload, 'nit_ci', '—'),
                    'razon_social' => Arr::get($payload, 'razon_social', '—'),
                    'nombre_estudiante' => $student?->full_name ?? 'N/A',
                    'tipo_pago' => $voucher->payment_type ?? 'N/A',
                    'monto' => $voucher->amount,
                    'cuenta' => $account,
                    'estado' => ucfirst($voucher->billing_status ?? 'pendiente'),
                    'custom_id' => 'VC-'.$voucher->id,
                    'banco' => $bank?->name ?? '—',
                    'fecha_registro' => $this->formatDate($voucher->received_at),
                    'operation_reference' => Arr::get($payload, 'operation_number', '—'),
                ];
            });

        $rows = $salesRows->concat($voucherRows)
            ->sortByDesc('fecha')
            ->values();

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
            'banks' => $banks,
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
                'Nro',
                'Fecha',
                'Número de factura',
                'NIT/CI',
                'Razón social',
                'Nombre estudiante',
                'Tipo de pago',
                'Monto',
                'Cuenta',
                'Estado',
                'ID',
                'Banco',
                'Fecha registro',
                'N° operación',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['nro'],
                    $row['fecha'],
                    $row['numero_factura'],
                    $row['nit_ci'],
                    $row['razon_social'],
                    $row['nombre_estudiante'],
                    $row['tipo_pago'],
                    $row['monto'],
                    $row['cuenta'],
                    $row['estado'],
                    $row['custom_id'],
                    $row['banco'],
                    $row['fecha_registro'],
                    $row['operation_reference'] ?? '—',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
