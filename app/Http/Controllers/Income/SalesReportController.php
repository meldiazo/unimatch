<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\SalesBookEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SalesReportController extends Controller
{
    public function index(Request $request): View
    {
        $entries = SalesBookEntry::with('batch')
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('ingresos.sales_report.index', [
            'entries' => $entries,
        ]);
    }

    public function export(Request $request)
    {
        $format = strtolower($request->query('format', 'pdf'));

        $entries = SalesBookEntry::with('batch')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $filename = 'reporte-diario';

        return match ($format) {
            'pdf' => $this->downloadPdf($entries, $filename.'.pdf'),
            'txt' => $this->downloadText($entries, $filename.'.txt'),
            'xls' => $this->downloadXls($entries, $filename.'.xls'),
            default => abort(400, 'Formato no válido.'),
        };
    }

    public function update(Request $request, SalesBookEntry $entry): RedirectResponse
    {
        $data = $request->validate(
            [
                'custom_id' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('sales_book_entries', 'custom_id')->ignore($entry->id),
                ],
                'bank_name' => ['nullable', 'string', 'max:100'],
                'recorded_date' => ['nullable', 'date'],
                'operation_reference' => [
                    'nullable',
                    'string',
                    'max:150',
                    Rule::unique('sales_book_entries', 'operation_reference')->ignore($entry->id),
                ],
            ],
            [
                'custom_id.unique' => 'El ID ya fue utilizado en otro registro.',
                'operation_reference.unique' => 'El número de operación ya existe en el reporte.',
            ]
        );

        if (! empty($data['recorded_date']) && $entry->invoice_date) {
            if (Carbon::parse($data['recorded_date'])->lt($entry->invoice_date)) {
                return back()
                    ->withErrors(['recorded_date' => 'La fecha registrada no puede ser anterior a la fecha del reporte.'])
                    ->withInput();
            }
        }

        $entry->update($data);

        return back()->with('status', 'Registro actualizado.');
    }

    protected function downloadPdf(Collection $entries, string $filename)
    {
        $pdf = Pdf::loadView('exports.sales-report', [
            'entries' => $entries,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    protected function downloadText(Collection $entries, string $filename)
    {
        $header = implode(';', [
            'Nro',
            'Fecha',
            'Número factura',
            'NIT/C.I.',
            'Razón social',
            'Nombre estudiante',
            'Tipo pago',
            'Monto',
            'Cuenta',
            'Estado',
            'ID',
            'Banco',
            'Fecha registro',
            'Operación',
        ]);

        $rows = $entries->map(function (SalesBookEntry $entry) {
            return implode(';', [
                $entry->legacy_number ?? '',
                optional($entry->invoice_date)->format('Y-m-d') ?? '',
                $entry->invoice_number ?? '',
                $entry->nit_ci ?? '',
                $entry->razon_social ?? '',
                $entry->student_name ?? '',
                $entry->payment_type ?? '',
                $this->formatDecimal($entry->amount),
                $entry->account_label ?? '',
                $entry->state_label ?? '',
                $entry->custom_id ?? '',
                $entry->bank_name ?? '',
                optional($entry->recorded_date)->format('Y-m-d') ?? '',
                $entry->operation_reference ?? '',
            ]);
        });

        $content = $header."\n".$rows->implode("\n");

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function downloadXls(Collection $entries, string $filename)
    {
        $html = view('exports.tables.sales-report', [
            'entries' => $entries,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function formatDecimal($value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
